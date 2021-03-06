<?php

namespace Galexth\ElasticsearchOdm\Relations;


use Galexth\ElasticsearchOdm\Collection;
use Galexth\ElasticsearchOdm\Model;
use Galexth\ElasticsearchOdm\Relation;
use Elastica\Query\Ids;

class Single extends Relation
{
    /**
     * @var string
     */
    protected $foreignKey;

    /**
     * @var string
     */
    protected $localKey;

    /**
     * Nested constructor.
     * @param string $className
     * @param Model $parent
     * @param string $foreignKey
     * @param string $localKey
     */
    public function __construct($className, Model $parent, string $foreignKey, string $localKey)
    {
        parent::__construct($className, $parent);

        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    /**
     * @param array $models
     * @param string $relation
     * @return Collection
     */
    public function getEager(array $models, string $relation): Collection
    {
        $ids = array_values(array_unique(array_filter(array_map(function ($model) use ($relation) {
            return $model->{$relation}[$this->foreignKey] ?? null;
        }, $models))));

        if (! $ids) {
            return $this->related->newCollection();
        }

        return $this->query->setQuery(new Ids($ids))->setSize(count($ids))->get();
    }

    /**
     * Get all of the primary keys for an array of models.
     *
     * @param  array   $models
     * @param  string  $key
     * @return array
     */
    protected function getKeys(array $models, $key)
    {
        return array_map(function ($value) use ($key) {
            return $value[$key] ?? null;
        }, $models);
    }

    /**
     * @param array $models
     * @param Collection $results
     * @return array
     */
    public function match(array $models, Collection $results, string $relation): array
    {
        $results = $results->keyBy(function (Model $item) {
            return $item->getId();
        });

        /** @var Model $model */
        foreach ($models as $model) {
            $relatedModel = null;

            if (isset($model->{$relation}[$this->localKey])) {
                $relatedModel = $results->get($model->{$relation}[$this->localKey]);
            }

            $model->addRelation($relation, $relatedModel);
        }

        return $models;
    }
}