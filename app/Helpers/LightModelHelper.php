<?php

namespace App\Helpers;

use stdClass;

trait LightModelHelper
{
    // =========================>
    // ## Dumping field
    // =========================>
    protected function scopeDumpField(
        $query,    //? query
        $request,  //? request field
    ) {
        $fillableAttributes = array_intersect_key(
            $request->only($query->getModel()->getFillable()),
            array_flip($query->getModel()->getFillable())
        );

        $query->getModel()->fill($fillableAttributes);
    }

    // =========================>
    // ## Dumping field
    // =========================>
    protected function scopeSelectableColumns(
        $query,                  //? query
        array $selectable = []   //? when includes custom selectable
    ) {
        $query->select(array_merge($query->getModel()->selectable, $selectable));
    }

    // =========================>
    // ## Search
    // =========================>
    protected function scopeSearch(
        $query,                  //? query
        string $keyword = "",    //? keyword of search
        array $searchable = []   //? when includes custom searchable
    ) {
        if (!$keyword) return;

        $model = $query->getModel();
        $searchables = array_merge($model->searchable, $searchable);

        $query->where(function ($query) use ($keyword, $searchables) {
            foreach ($searchables as $searchable) {
                $parts = explode('.', $searchable);
                $column = array_pop($parts);
                $relation = implode('.', $parts);

                if (!$relation) {
                    $query->orWhere($column, 'LIKE', "%$keyword%");
                } else {
                    $query->orWhereRelationRaw($relation, 'LOWER(?) LIKE LOWER(?)', [$column, "%$keyword%"]);
                }
            }
        });
    }


    // =========================>
    // ## Filter
    // =========================>
    protected function scopeFilter(
        $query,                 //? query
        array|stdClass|null $filters     //? rules of filter
    ) {
        if (!$filters) return;

        // Convert stdClass to array if needed
        if ($filters instanceof stdClass) {
            $filters = json_decode(json_encode($filters), true);
        }

        foreach ($filters as $filterable => $filter) {
            if (is_array($filter) || is_object($filter)) {
                $filterArray = is_object($filter) ? json_decode(json_encode($filter), true) : $filter;

                $type = $filterArray['type'] ?? null;
                $column = $filterArray['column'] ?? null;
                $value = $filterArray['value'] ?? null;

                if (!$type || !$column) continue;

                // Handle array values
                if (is_array($value)) {
                    $value = implode(',', $value);
                }

                $filterablePieces = explode('.', $column);
                $columnName = array_pop($filterablePieces);
                $relation = implode('.', $filterablePieces);
            } else {
                // Handle old string format "column:type:value"
                [$type, $value] = explode(':', $filter) + [null, null];
                $filterablePieces = explode('.', $filterable);
                $columnName = array_pop($filterablePieces);
                $relation = implode('.', $filterablePieces);
            }

            switch ($type) {
                case 'eq':
                    if (!$relation) {
                        $query->where($columnName, $value);
                    } else {
                        $query->whereRelation($relation, $columnName, $value);
                    }
                    break;
                case 'ne':
                    if (!$relation) {
                        $query->where($columnName, '!=', $value);
                    } else {
                        $query->whereRelation($relation, $columnName, '!=', $value);
                    }
                    break;
                case 'in':
                    if (!$relation) {
                        $query->whereIn($columnName, explode(',', $value));
                    } else {
                        $query->whereRelation($relation, $columnName, explode(',', $value));
                    }
                    break;
                case 'ni':
                    if (!$relation) {
                        $query->whereNotIn($columnName, explode(',', $value));
                    } else {
                        $query->whereRelationNotIn($relation, $columnName, explode(',', $value));
                    }
                    break;
                case 'bw':
                    if (!$relation) {
                        $query->where($columnName, '>=', explode(',', $value)[0])
                            ->where($columnName, '<=', explode(',', $value)[1]);
                    } else {
                        $query->whereRelation($relation, $columnName, '>=', explode(',', $value)[0])
                            ->whereRelation($relation, $columnName, '<=', explode(',', $value)[1]);
                    }
                    break;
                default:
                    break;
            }
        }
    }
}
