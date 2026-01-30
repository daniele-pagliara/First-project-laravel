@extends('auth.master-user')
@section('content-user')
<sidebar-provider>
    <tab-utenti
        dashboard-url="{{ route('pagine.home') }}"
        database-url="{{ route('pagine.cerca-dati') }}"
        home-url="{{ url('/') }}"
        logout-url="{{ route('logout') }}"
        csrf-token="{{ csrf_token() }}"
        :user-data="{{ Auth::user() }}"
        :users="{{ $usersForVue->toJson() }}">
    </tab-utenti>
</sidebar-provider>
@endsection