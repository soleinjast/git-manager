@extends('layouts.app')

@section('content')
    <div id="app">
        <p><button v-on:click="counter += 1" type="button" class="btn btn-danger">Primary</button></p>
        <p>The button has been clicked @{{ counter }} times</p>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let app = new Vue({
                el: '#app',
                data: {
                    counter:0
                },
            });
        });
    </script>
@endsection
