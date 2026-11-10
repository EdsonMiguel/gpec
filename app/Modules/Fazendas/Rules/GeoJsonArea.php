<?php

namespace App\Modules\Fazendas\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use JsonException;
use stdClass;

class GeoJsonArea implements ValidationRule
{
    public const MAX_BYTES = 512000;

    private int $vertices = 0;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $this->vertices = 0;
        try {
            $document = is_string($value) && strlen($value) <= self::MAX_BYTES
                ? json_decode($value, false, 64, JSON_THROW_ON_ERROR) : null;
            json_encode($document, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $document = null;
        }
        if (! $document instanceof stdClass || isset($document->crs) || ! $this->validDocument($document)) {
            $fail('A marcação do mapa é inválida ou muito grande. Refaça o contorno usando polígonos fechados.');
        }
    }

    private function validDocument(stdClass $document): bool
    {
        if (($document->type ?? null) === 'FeatureCollection') {
            if (! isset($document->features) || ! is_array($document->features)
                || count($document->features) < 1 || count($document->features) > 50) {
                return false;
            }
            foreach ($document->features as $feature) {
                if (! $feature instanceof stdClass || ($feature->type ?? null) !== 'Feature' || ! $this->validFeature($feature)) {
                    return false;
                }
            }

            return true;
        }

        return ($document->type ?? null) === 'Feature' ? $this->validFeature($document) : $this->validGeometry($document);
    }

    private function validFeature(stdClass $feature): bool
    {
        return property_exists($feature, 'properties') && ($feature->properties === null || $feature->properties instanceof stdClass)
            && isset($feature->geometry) && $feature->geometry instanceof stdClass && $this->validGeometry($feature->geometry);
    }

    private function validGeometry(stdClass $geometry): bool
    {
        if (! isset($geometry->coordinates) || ! is_array($geometry->coordinates)) {
            return false;
        }
        if (($geometry->type ?? null) === 'Polygon') {
            return $this->validPolygon($geometry->coordinates);
        }
        if (($geometry->type ?? null) !== 'MultiPolygon' || count($geometry->coordinates) < 1 || count($geometry->coordinates) > 50) {
            return false;
        }
        foreach ($geometry->coordinates as $polygon) {
            if (! is_array($polygon) || ! $this->validPolygon($polygon)) {
                return false;
            }
        }

        return true;
    }

    private function validPolygon(array $rings): bool
    {
        if (count($rings) < 1 || count($rings) > 100) {
            return false;
        }
        foreach ($rings as $ring) {
            if (! is_array($ring) || count($ring) < 4 || $ring[0] != $ring[array_key_last($ring)]) {
                return false;
            }
            $area = 0;
            foreach ($ring as $index => $position) {
                if (! is_array($position) || count($position) < 2 || count($position) > 3 || ! array_is_list($position)) {
                    return false;
                }
                foreach ($position as $coordinate) {
                    if ((! is_int($coordinate) && ! is_float($coordinate)) || ! is_finite((float) $coordinate)) {
                        return false;
                    }
                }
                if (abs($position[0]) > 180 || abs($position[1]) > 90 || ++$this->vertices > 10000) {
                    return false;
                }
                if ($index > 0) {
                    $area += ($ring[$index - 1][0] - $ring[0][0]) * ($position[1] - $ring[0][1])
                        - ($position[0] - $ring[0][0]) * ($ring[$index - 1][1] - $ring[0][1]);
                }
            }
            if ($area == 0) {
                return false;
            }
        }

        return true;
    }
}
