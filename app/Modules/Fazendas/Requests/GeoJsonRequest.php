<?php

namespace App\Modules\Fazendas\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class GeoJsonRequest extends FormRequest
{
    public function validatedWithGeoJson(): array
    {
        $attributes = $this->validated();
        if (array_key_exists('geojson', $attributes) && $attributes['geojson'] !== null) {
            $attributes['geojson'] = json_decode($attributes['geojson'], false, 64, JSON_THROW_ON_ERROR);
        }

        return $attributes;
    }
}
