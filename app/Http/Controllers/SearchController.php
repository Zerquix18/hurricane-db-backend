<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Hurricane;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->validate(
            $request,
            [
                'name' => 'bail|max:255',
                'season' => 'bail|numeric',
            ]
        );
        $query = Hurricane::query();
        $wheres = [];

        $name = $request->input('name');
        $name = preg_replace('/[^\w]/', '', $name); // to paranoid

        if ($name) {
            $query = $query->where('name', 'like', "%{$name}%");
        }

        $season = $request->input('season');

        if ($season) {
            $query = $query->where('season', '=', $season);
        }

        $hurricanes = $query->get();

        return $hurricanes;
    }
}
