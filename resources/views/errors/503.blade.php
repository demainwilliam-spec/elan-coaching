@extends('errors.layout')

{{-- 503 : site en maintenance (php artisan down), par exemple pendant une mise à jour --}}
@section('title', 'Maintenance')
@section('code', '503')
@section('heading', 'Site en maintenance')
@section('message', "Le site est en cours de mise à jour. Revenez dans quelques minutes.")
