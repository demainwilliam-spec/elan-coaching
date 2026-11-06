@extends('errors.layout')

{{-- 429 : limite du middleware throttle atteinte (contact, réservation, connexion...) --}}
@section('title', 'Trop de tentatives')
@section('code', '429')
@section('heading', 'Trop de tentatives')
@section('message', "Vous avez fait trop d'essais en peu de temps. Patientez quelques minutes avant de réessayer.")
