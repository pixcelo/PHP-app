This is test.blade.php<br/>

@foreach ($models as $model)
    {{ $model->id }} <br/>
    {{ $model->text }} <br/>
@endforeach