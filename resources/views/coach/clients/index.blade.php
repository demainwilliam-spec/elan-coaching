@extends('layouts.coach')

@section('title', 'Clients')

@section('content')
    <h2>Clients</h2>

    <p class="coach-summary">{{ $clients->count() }} {{ $clients->count() > 1 ? 'comptes' : 'compte' }}</p>

    @if ($clients->isNotEmpty())
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Nom</th>
                    <th scope="col">Email</th>
                    <th scope="col">Adresse</th>
                    <th scope="col">Inscrit depuis</th>
                    <th scope="col">À venir</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($clients as $client)
                    <tr>
                        <td data-label="Nom">{{ $client->last_name }} {{ $client->first_name }}</td>
                        <td data-label="Email"><a href="mailto:{{ $client->email }}">{{ $client->email }}</a></td>
                        <td data-label="Adresse">{{ $client->hasVerifiedEmail() ? 'Confirmée' : 'À confirmer' }}</td>
                        <td data-label="Depuis">{{ $client->created_at->translatedFormat('j F Y') }}</td>
                        <td data-label="À venir">{{ $client->upcoming_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
