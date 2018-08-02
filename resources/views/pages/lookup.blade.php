@extends("templates.app")
@section("content")

    <div class="row justify-content-md-center">
        <div class="col col-lg-4">

        @if($errors->any())
            <h4>{{$errors->first()}}</h4>
        @endif

            @if(isset($msg))
                <h4>{{$msg}}</h4>
            @endif

        <h2>Lookup user</h2>


        {!! Form::open(["action" => "MRegController@LookupUser", "method"=> "POST"]) !!}
            <div class="form-group">
                {{Form::label("MSISDN","MSISDN")}}
                {{Form::text("msisdn","",["class" => "form-control", "placeholder" => ""])}}

            </div>
        {{Form::submit("Submit",["class" => "btn btn-primary"])}}
        {!! Form::close() !!}
        </div>
    </div>
@endsection