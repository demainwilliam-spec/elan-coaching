@extends('errors.layout')

{{-- 419 : le jeton CSRF du formulaire a expiré (page restée ouverte trop longtemps) --}}
@section('title', 'Page expirée')
@section('code', '419')
@section('heading', 'Page expirée')
@section('message', "La page est restée ouverte trop longtemps. Revenez en arrière, rechargez la page, puis renvoyez le formulaire.")
