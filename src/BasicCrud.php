<?php

namespace BasicCrud;

use Illuminate\Http\Request;

/**
 * Trait BasicCrud
 * @package BasicCrud
 */
trait BasicCrud
{

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {

        $data = $request->all();

        $filter = $this->filter($request);
        $fields = $this->select($request);

        $sort = ['id','asc'];    
        if (isset($request->sort)) {
            if (is_array($request->sort)) {
                foreach ($request->sort as $k => $v) {
                    if ($this->isAttribute($v)) {
                        $sort = [$v,$k];
                    }
                }
            }
        }

        $data = $this->model::when(count($filter['regular'])>0,function($query) use ($filter){
          return $query->where($filter['regular']);  
        })
        ->when(count($filter['between'])>0,function($query) use ($filter){
            $c = count($filter['between']);
            $i = 0;
            foreach ($filter['between'] as $k => $v) {
                if (++$i === $c) {
                    return $query->whereBetween($k,$v);
                } else {
                    $query->whereBetween($k,$v);  
                }
            }
        })
        ->when(count($filter['in'])>0,function($query) use ($filter){
            $c = count($filter['in']);
            $i = 0;
            foreach ($filter['in'] as $k => $v) {
                if (++$i === $c) {
                    return $query->whereIn($k,$v);
                } else {
                    $query->whereIn($k,$v);  
                }
            }
        })
        ->when($sort, function($query) use ($sort) {
            return $query->orderBy($sort[0], $sort[1]);
        })->select($fields)->paginate( (isset($request->entries))?$request->entries:20 );
        
        $code = 200;
        if ($data->items()) {
            $item = $data->items();
            $metadata = [
                'selectedPage' => $data->currentPage(), 
                'selectedItem' => NULL, 
                'totalPage' => $data->lastPage(), 
                'totalItem' => $data->total(), 
                'totalItemPerPage' => count($data->items()) 
            ];
        } else {
            $item = [];
            $metadata = [];
        }
        
        $this->responseCode = $code;
        
        $this->results = $item;
        $this->metaData = $metadata;
        if ($request->all()) {
            $this->request = [
                'get'=>$request->all()
            ];
        }

        return $this->response();
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store (Request $request){

        $this->validate($request, $this->insertValidation);

        $data = $this->model::create($request->all());

        $this->responseCode = 200;
        $this->results = [$data];
        $this->request = [
            'post'=>$request->all()
        ];

        return $this->response();
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function update(Request $request){

        $this->validate($request, $this->updateValidation);

        $data = $this->model::find($request->id);
        if ($data) {
            $data->update($request->all());
            
            $this->responseCode = 200;
            $this->results = [$data];
            $this->request = [
                'get'=>$request->all()
            ];
        }else{
            $this->responseCode = 422;
            $this->results = [];
            $this->messages = ['id'=>'ID can\'t be found'];
            $this->request = [
                'post'=>$request->all()
            ];
        }

        return $this->response();
    }

    /**
     * @param $id
     * @param string $type
     * @return mixed
     */
    public function destroy($id, $type='hard'){
        if ($type=='hard') {
            $data = $this->hardDelete($id);
        } else {
            if (isset($this->dataDelete)) {
                $data = $this->softDelete($id,$this->dataDelete);
            } else {
                $data = $this->hardDelete($id);
            }
        }
        
        return $data;
    }

    /**
     * @param $id
     * @param $dataDelete
     * @return mixed
     */
    protected function softDelete($id, $dataDelete)
    {
        $data = $this->model::find($id);
        if ($data) {
            $data->update($dataDelete);
            
            $this->responseCode = 200;
            $this->results = [$data];
            $this->messages = 'Deleted';
            $this->request = [
                'get'=>[
                    'id'=>$id
                ]
            ];
        }else{
            $this->responseCode = 422;
            $this->results = [];
            $this->messages = ['id'=>'ID can\'t be found'];
        }
        return $this->response();
    }

    /**
     * @param $id
     * @return mixed
     */
    protected function hardDelete($id)
    {
        $data = $this->model::find($id);
        if ($data) {
            $data->delete();
        
            $this->responseCode = 200;
            $this->results = [$data];
            $this->messages = 'Deleted';
            $this->request = [
                'get'=>[
                    'id'=>$id
                ]
            ];
        } else {
            $this->responseCode = 422;
            $this->results = [];
            $this->messages = ['id'=>'ID can\'t be found'];
        }
        
        return $this->response();
    }

    /**
     * @param $request
     * @return array|string
     */
    protected function select($request)
    {

        $fields = explode(',',$request->fields);
        $vFields = [];
        foreach ($fields as $f) {
            if ($this->isAttribute($f)) {
                $vFields[] = $f;
            }
        }

        if (count($vFields) <= 0) {
            $vFields = '*';
        }

        return $vFields;
    }

    /**
     * @param $request
     * @return array
     */
    protected function filter($request)
    {
        $filter = ['regular'=>[],'between'=>[],'in'=>[]];
        
        foreach ($request->all() as $key => $value) {
            if ( $this->isAttribute($key)) {

                $operator = ['gte'=>'>=','lte'=>'<=','gt'=>'>','lt'=>'<','like'=>'like','between'=>'between'];

                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        if (array_key_exists($k,$operator)) {
                            if ($k=='between') {
                                if (isset($v['start']) and isset($v['end'])) {
                                    $filter['between'][$key][] = [$v['start'],$v['end']];
                                }
                            } else {
                                $filter['regular'][] = [$key, $operator[$k], ($v=="null")?null:$v];
                            }
                        }elseif(is_numeric($k) and $k >= 0){
                            $filter['in'][$key][] = [($v=="null")?null:$v];
                        }
                    }
                } else { 
                    $filter['regular'][] = [$key, ($value=="null")?null:$value];
                }
            }
        }
        return $filter;
    }

    /**
     * @param $attr
     * @return mixed
     */
    protected function isAttribute($attr){$a = \Schema::hasColumn(app($this->model)->getTable(),$attr);return $a;}

    /**
     * @param type $request 
     * @param type $rule 
     * @return mixed
     */
    protected function validator($request, $rule)
    {
        $validator = Validator::make($request->all(), $rule);

        if ($validator->fails()) {
            $this->responseCode = 422;
            $this->results = ['d'];
            $this->message = $validator;
            return $this->response();
        }
    }

}
