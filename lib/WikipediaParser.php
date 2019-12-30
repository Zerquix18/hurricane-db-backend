<?php
/**
 * Fetches info from Wikipedia
 */
namespace Lib;

use App\Hurricane;
use Illuminate\Support\Str;

class WikipediaParser
{
    private $hurricane;

    public function __construct(Hurricane $hurricane)
    {
        $this->hurricane = $hurricane;
    }
    /*
     * We're attempting to find the correct Wikipedia article for a particular hurricane
     * so we can extract a short description, the image(s), casualties and damage.
     * But we face 3 problems:
     * 1. The name of the hurricane can be reused across seasons (and also across basins! even if it's retired from one)
     * 2. The name of the article could go from "Tropical Storm/Depression X" to "Hurricane X", depending on the intensity
     * 3. Not all hurricanes have their own Wikipedia article, but pretty much all have information in the season page.
     * 
    **/ 
    public function getData(): array
    {
        // 1. Extract the information from the season page, even if there's no page for this particular storm
        // we still get some info and (maybe) photos from this page.
        $info_from_season_page = $this->findHurricaneInSeasonPage();
        if (! $info_from_season_page) {
            // this is a weird kind of hurricane
            return [];
        }

        $info_from_main_article = [];
        if ($info_from_season_page['main_article']) {
            $info_from_main_article = $this->getInfoFromMainArticle($info_from_season_page['main_article']);
            return $info_from_main_article;
        } else {
            return [
                'first_paragraph' => $info_from_season_page['main_paragraph'],
                'first_paragraph_source' => $info_from_season_page['main_paragraph_source'],
                'min_range_fatalities' => null,
                'max_range_fatalities' =>null,
                'min_range_damage' => null,
                'max_range_damage' => null,
                'affected_areas' => [],
                'default_image' => count($info_from_season_page['images']) > 0 ? $info_from_season_page['images'][0] : null,
                'images' => '', // todo
            ];
        }
    }

    /**
     * Returns all the information we can find on a season page about a hurricane
     * Either null if we don't find the hurricane, or:
     * 'main_article' => string|null
     * 'main_paragraph' => string|null
     * 'images' => string[] // empty if none
     * 'main_paragraph_source' => string
     */
    private function findHurricaneInSeasonPage(): ?array
    {
        $season = $this->hurricane->season;
        $basin  = ucfirst($this->hurricane->basin);
        $name   = $this->hurricane->name;
        
        $wikipedia_page_name = sprintf("%d_%s_hurricane_season", $season, $basin);
        if ($season === 2005) {
            // ONLY EXCEPTION. WASN'T THIS A GREAT YEAR -_- 
            $wikipedia_page_name = "List_of_storms_in_the_2005_Atlantic_hurricane_season";
        }
        $wikipedia_url = sprintf(
            "https://en.wikipedia.org/api/rest_v1/page/mobile-sections/%s",
            $wikipedia_page_name
        );

        $json = file_get_contents($wikipedia_url);
        if (! $json) {
            return null; // :(
        }

        $json = json_decode($json, true);

        // im just doing this out of paranoia:
        $sections = collect($json['lead']['sections']);
        $this_storm_section = $sections->first(function ($section) use ($name) {
            return key_exists('line', $section) && stripos($section['line'], $name) !== false;
        });

        $sections_content = collect($json['remaining']['sections']);

        $section_content = $sections_content->first(function ($section) use ($this_storm_section) {
            return $section['id'] === $this_storm_section['id'];
        });

        $html = $section_content['text'];

        if (trim($html) === '') {
            // sometimes they don't exist, like Hurricane One in 1853 https://en.wikipedia.org/wiki/1853_Atlantic_hurricane_season
            return null;
        }

        $domdocument = new \DOMDocument();
        libxml_use_internal_errors(true); // :( :@
        $domdocument->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

        $images = [];
        $main_paragraph = null;
        $main_article = null;

        /// images

        $image_nodes = $domdocument->getElementsByTagName('img');
        foreach ($image_nodes as $image) {
            $src = $image->getAttribute('src');
            $srcset = $image->getAttribute('srcset');
            if ($srcset) {
                $sources = explode(',', $srcset);
                $source_and_size = trim(end($sources));
                $source_and_size = explode(' ', $source_and_size);
                $src = $source_and_size[0]; // todo: bring a bigger size just by tweaking the "px" in the URL
            }

            if (! Str::startsWith($src, 'https:')) {
                $src = 'https:' . $src;
            }
            $images[] = $src;
        }

        // main paragraph

        $paragraphs = $domdocument->getElementsByTagName('p');
        foreach ($paragraphs as $paragraph) {
            $main_paragraph = strip_tags($paragraph->ownerDocument->saveHTML($paragraph));
            break;
        }

        // main article
        $domxpath = new \DOMXPath($domdocument);
        $elem = $domxpath->query("//*[contains(@class, 'navigation-not-searchable')]");
        if ($elem->length > 0) {
            $elem = $elem->item(0);
            $title = $elem->childNodes[0]->textContent;
            if (stripos($title, 'Main') !== false) {
                $anchor = $elem->childNodes[1];
                $href = $anchor->getAttribute('href');
                $main_article = str_replace('/wiki/', '', $href); // :D
            }
        }


        $web_url = sprintf("https://en.wikipedia.org/wiki/%s", $wikipedia_page_name);
        $main_paragraph_source = sprintf("%s#%s", $web_url, $section_content['anchor']);

        return [
            'main_article' => $main_article,
            'images' => $images,
            'main_paragraph' => $main_paragraph,
            'main_paragraph_source' => $main_paragraph_source,
        ];
    }

    /**
     * The particular storm has been confirmed to have a wikipedia article
     * This gives us basic info such as:
     * [
     * main_paragraph: string;
     * min_range_fatalities: int;
     * max_range_fatalities: int;
     * min_range_damage: float;
     * max_range_damager: float;
     * affected_areas: string[];
     * images: { url: string, description: string }[];
     * default_image: string;
     *
     */
    private function getInfoFromMainArticle(string $main_article): array
    {
        $wikipedia_url = sprintf(
            "https://en.wikipedia.org/api/rest_v1/page/mobile-sections/%s",
            $main_article
        );

        // file_get_contents fails following redirects

        $ch = curl_init($wikipedia_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $json = curl_exec($ch);
        curl_close($ch);

        if (! $json) {
            return null; // :(
        }

        $json = json_decode($json, true);

        $main_section = $json['lead']['sections'][0]['text'];

        $domdocument = new \DOMDocument;
        $domdocument->loadHTML(mb_convert_encoding($main_section, 'HTML-ENTITIES', 'UTF-8'));

        // first paragraph:
        $paragraphs = $domdocument->getElementsByTagName('p');
        $first_paragraph = null;
        foreach ($paragraphs as $paragraph) {
            $first_paragraph = strip_tags($paragraph->ownerDocument->saveHTML($paragraph));
            break;
        }

        // fatalities, damage, affected areas
        $min_range_fatalities = null;
        $max_range_fatalities = null;
        $min_range_damage = null;
        $max_range_damage = null;
        $affected_areas = [];

        $domxpath = new \DOMXPath($domdocument);
        $table_lookup = $domxpath->query("//*[contains(@class, 'infobox')]");
        if ($table_lookup->length > 0) {
            $table = $table_lookup->item(0);
            $tbody = $table->childNodes[1];
            foreach ($tbody->childNodes as $tr) {
                $th = $tr->childNodes[0];
                $td = $tr->childNodes[1];
                if ($th->textContent === "Fatalities") {
                    $fatalities_string = $td->textContent;
                }
                if ($th->textContent === "Damage") {
                    $damage_string = $td->textContent;
                }
                if ($th->textContent === "Areas affected") {
                    foreach ($td->childNodes as $node) {
                        if ($node->nodeName === "a") {
                            $affected_areas[] = $node->textContent;
                        }
                    }
                }
            }
        }

        if (isset($fatalities_string)) {
            $fatalities = $this->parseFatalitiesString($fatalities_string);
            if ($fatalities) {
                $min_range_fatalities = $fatalities['min_range_fatalities'];
                $max_range_fatalities = $fatalities['max_range_fatalities'];
            }
        }
        if (isset($damage_string)) {
            $damage = $this->parseDamageString($damage_string);
            if ($damage) {
                $min_range_damage = $damage['min_range_damage'];
                $max_range_damage = $damage['max_range_damage'];
            }
        }

        // default_image
        $default_image = null;
        $img_lookup = $domdocument->getElementsByTagName('img');
        if ($img_lookup->length > 0) {
            $img = $img_lookup->item(0);
            $src = $img->getAttribute('src');
            $srcset = $img->getAttribute('srcset');
            if ($srcset) {
                $sources = explode(',', $srcset);
                $source_and_size = trim(end($sources));
                $source_and_size = explode(' ', $source_and_size);
                $src = $source_and_size[0]; // todo: bring a bigger size just by tweaking the "px" in the URL
            }

            if (! Str::startsWith($src, 'https:')) {
                $src = 'https:' . $src;
            }
            $default_image = $src;
        }

        // images - todo
        $images = [];

        $first_paragraph_source = sprintf("https://en.wikipedia.org/wiki/%s", $main_article);

        return [
            'first_paragraph' => $first_paragraph,
            'first_paragraph_source' => $first_paragraph_source,
            'min_range_fatalities' => $min_range_fatalities,
            'max_range_fatalities' => $max_range_fatalities,
            'min_range_damage' => $min_range_damage,
            'max_range_damage' => $max_range_damage,
            'affected_areas' => $affected_areas,
            'default_image' => $default_image,
            'images' => $images,
        ];
    }

    private function parseFatalitiesString(string $fatalities_string): ?array
    {
        // eg: "3,059 total"
        $regex1 = preg_match('/([\d\,]+) total/', $fatalities_string, $matches);
        if ($matches) {
            $fatalities = (int) str_replace(',', '', $matches[1]);
            return [
                'min_range_fatalities' => $fatalities,
                'max_range_fatalities' => $fatalities,
            ];
        }
        // just to be 100%%%%%%%% safe
        $fatalities_string = str_replace('–', '-', $fatalities_string);
        // eg "1,245–1,836 total" (https://en.wikipedia.org/wiki/Hurricane_Katrina)
        $regex2 = preg_match('/^([\d\,\-]+) total/', $fatalities_string, $matches);
        if ($matches) {
            $fatalities = explode("-", $matches[1]);
            $min_range_fatalities = $fatalities[0];
            $max_range_fatalities = $fatalities[1];
            return [
                'min_range_fatalities' => (int) str_replace(',', '', $min_range_fatalities),
                'max_range_fatalities' => (int) str_replace(',', '', $max_range_fatalities),
            ];
        }
        // eg: "3 direct, 5 indirect"
        $regex3 = preg_match('/^([\d]+) direct, ([\d]+) indirect$/', $fatalities_string, $matches);
        if ($matches) {
            $direct = (int) $matches[1];
            $indirect = (int) $matches[2];
            return [
                'min_range_fatalities' => $direct + $indirect,
                'max_range_fatalities' => $direct + $indirect,
            ];
        }
        
        $regex4 = preg_match('/^([\d]+) direct$/', $fatalities_string, $matches);
        if ($matches) {
            $direct = (int) $matches[1];
            return [
                'min_range_fatalities' => $direct,
                'max_range_fatalities' => $direct,
            ];
        }

        $regex5 = preg_match('/^([\d]+) indirect$/', $fatalities_string, $matches);
        if ($matches) {
            $indirect = (int) $matches[1];
            return [
                'min_range_fatalities' => $indirect,
                'max_range_fatalities' => $indirect,
            ];
        }

        return null;
    }

    private function parseDamageString(string $damage_string): ?array
    {
        $regex = preg_match('/^\$([\d\.]+) (million|billion)/', $damage_string, $matches);
        if ($matches) {
            $amount = (int) $matches[1];
            if ($matches[2] === 'million') {
                $amount *= 1000000;
            }
            if ($matches[2] === 'billion') {
                $amount *= 1000000000;
            }
            return [
                'min_range_damage' => $amount,
                'max_range_damage' => $amount,
            ];
        }

        $regex2 = preg_match('/^\$([\d\.\,]+)/', $damage_string, $matches);
        if ($matches) {
            $amount = (float) str_replace(',', '', $matches[1]);
            return [
                'min_range_damage' => $amount,
                'max_range_damage' => $amount,
            ];
        }

        return null;
    }
}
