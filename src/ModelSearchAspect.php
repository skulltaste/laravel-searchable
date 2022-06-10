<?php

namespace Spatie\Searchable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Traits\ForwardsCalls;
use Spatie\Searchable\Exceptions\InvalidModelSearchAspect;
use Spatie\Searchable\Exceptions\InvalidSearchableModel;

class ModelSearchAspect extends SearchAspect
{
    use ForwardsCalls;

    /** @var \Illuminate\Database\Eloquent\Model */
    protected $model;

    /** @var array */
    protected $attributes = [];
    /** @var array */
    protected $relationshipAttributes = [];
    /** @var array */
    protected $type = [];
    /** @var array */
    protected $operators = [];

    /** @var array */
    protected $values = [];

    /** @var string */
    protected $with;

    /** @var array */
    protected $callsToForward = [];

    /** @var array */
    protected $constraint_column = [];

    /** @var array */
    protected $constraint_value =[];

    public static function forModel(string $model, ...$attributes): self
    {
        return new self($model, $attributes);
    }

    /**
     * @param string $model
     * @param array|\Closure $attributes
     *
     * @throws \Spatie\Searchable\Exceptions\InvalidSearchableModel
     */
    public function __construct(string $model, $attributes = [])
    {
        if (! is_subclass_of($model, Model::class)) {
            throw InvalidSearchableModel::notAModel($model);
        }

        if (! is_subclass_of($model, Searchable::class)) {
            throw InvalidSearchableModel::modelDoesNotImplementSearchable($model);
        }

        $this->model = $model;

        if (is_array($attributes)) {
            $this->attributes = $attributes[0]['attributes'];
                $this->type = $attributes[0]['type'];
                $this->operators = $attributes[0]['operator'];
                $this->values = $attributes[0]['value'];

            if(isset($attributes[0]['with'])){
                $this->with = $attributes[0]['with'];
            }
            if(isset($attributes[0]['constraint_column'])){
                $this->constraint_column = $attributes[0]['constraint_column'];
                $this->constraint_value = $attributes[0]['constraint_value'];
            }
            return;
        }

        if (is_string($attributes)) {
            $this->attributes = SearchableAttribute::create($attributes);

            return;
        }

        if (is_callable($attributes)) {
            $callable = $attributes;

            $callable($this);

            return;
        }
    }

    public function addSearchableAttribute(string $attribute, bool $partial = true): self
    {
        $this->attributes[] = SearchableAttribute::create($attribute, $partial);

        return $this;
    }

    public function addExactSearchableAttribute(string $attribute): self
    {
        $this->attributes[] = SearchableAttribute::createExact($attribute);

        return $this;
    }

    public function getType(): string
    {
        $model = new $this->model();

        if (property_exists($model, 'searchableType')) {
            return $model->searchableType;
        }

        return $model->getTable();
    }

    public function getResults(string $term): Collection
    {
        if (empty($this->attributes)) {
            throw InvalidModelSearchAspect::noSearchableAttributes($this->model);
        }

        $query = ($this->model)::query();

        $this->addSearchConditions($query, $term);

        foreach ($this->callsToForward as $callToForward) {
            $this->forwardCallTo($query, $callToForward['method'], $callToForward['parameters']);
        }

        if ($this->limit) {
            $query->limit($this->limit);
        }

        return $query->get();
    }

    protected function addSearchConditions(Builder $query, string $term)
    {
        $attributes = $this->attributes;
        $type = $this->type;
        $values = $this->values;
        $operators = $this->operators;
        $with = $this->with;

        //$searchTerms = explode(' ', $term);
        if($this->constraint_column && !is_null($this->constraint_column[0])) {
            $constraint_columns = $this->constraint_column;
            $constraint_values = $this->constraint_value;
            foreach ($constraint_columns as $key => $constraint_column) {

                $value = mb_strtolower($constraint_values[$key], 'UTF8');
                $value = str_replace("\\", $this->getBackslashByPdo(), $value);
                $value = addcslashes($value, "%_");

                $query->where($constraint_column, '=', $value);
            }
        }
        foreach (Arr::wrap($attributes) as $key=> $attribute) {
            if($type[$key] == 'where') {
                $query->where(function (Builder $query) use ($attribute, $term, $values,$key) {
                    $sql = "LOWER({$query->getGrammar()->wrap($attribute)}) LIKE ? ESCAPE ?";
                    $searchTerms = explode(' ', $values[$key]);

                    foreach ($searchTerms as $searchTerm) {
                        $searchTerm = mb_strtolower($searchTerm, 'UTF8');
                        $searchTerm = str_replace("\\", $this->getBackslashByPdo(), $searchTerm);
                        $searchTerm = addcslashes($searchTerm, "%_");


                        $query->orWhereRaw($sql, ["%{$searchTerm}%", '\\']);

                    }

                });
            } else if($type[$key] == 'advanced' && $values[$key] && $values[$key] != ''){


                $value = mb_strtolower($values[$key], 'UTF8');
                $value = str_replace("\\", $this->getBackslashByPdo(), $value);
                $value = addcslashes($value, "%_");

                $query->where($attribute,$operators[$key],$value);

            } else if($type[$key] == 'with' && $values[$key] && $values[$key] != ''){
                $query->with([$with => function ($query) use ($attribute, $values, $key) {
                    $sql = "LOWER({$query->getGrammar()->wrap($attribute)}) LIKE ? ESCAPE ?";
                    $searchTerms = explode(' ', $values[$key]);

                    foreach ($searchTerms as $searchTerm) {
                        $searchTerm = mb_strtolower($values[$key], 'UTF8');
                        $searchTerm = str_replace("\\", $this->getBackslashByPdo(), $searchTerm);
                        $searchTerm = addcslashes($searchTerm, "%_");


                           $query->orWhereRaw($sql, ["%{$searchTerm}%", '\\']);
                    }

                    //$query->where($attribute['attribute'], 'like', '%' . $searchTerms . '%');
                }]);


            }

        }




            //dd($values);


/*

            foreach ($advancedAttributes as $key => $advancedAttribute) {
                $value = mb_strtolower($values[$key], 'UTF8');
                $value = str_replace("\\", $this->getBackslashByPdo(), $value);
                $value = addcslashes($value, "%_");

                $query->where($advancedAttribute,$operators[$key],$value);

            }


        foreach ($relationshipAttributes as $key => $relationshipAttribute) {
            $query->with([$this->with => function ($query) use ($relationshipAttribute, $searchTerms) {
                    $query->where($relationshipAttribute, 'like', '%' . $searchTerms . '%');
            }]);
        }

        if($with){
            $query->with($with);
        }*/
        //dd($query);
    }

    protected function getBackslashByPdo()
    {
        $pdoDriver = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($pdoDriver === 'sqlite') {
            return '\\\\';
        }

        return '\\\\\\';
    }

    public function __call($method, $parameters)
    {
        $this->callsToForward[] = [
            'method' => $method,
            'parameters' => $parameters,
        ];

        return $this;
    }
}
