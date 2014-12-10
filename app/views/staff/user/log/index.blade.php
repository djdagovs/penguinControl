@extends ('layout.master')

@section ('css')
@parent
<!--<link rel="stylesheet" href="/css/print.css" />-->
<link rel="stylesheet" media="print" href="/css/print.css" />
@endsection

@section ('pageTitle')
Facturatie &bull; Staff
@endsection

@section ('content')
<fieldset>
	<legend id="users">Gebruikers (Facturatie)</legend>
	<?php Paginator::setPageName ('userlog_page'); ?>
	{{ $userlogs->links () }}
	<table>
		<thead>
			<tr>
				<th></th>
				<th>
					Gebruikersnaam
				</th>
				<th>
					r-nummer
				</th>
				<th>
					Datum/Tijd
				</th>
				<th>
					Nieuw
				</th>
				<th>
					Facturatiestatus
				</th>
				<th>
					Primaire groep
				</th>
			</tr>
		</thead>
		<tbody>
			@foreach ($userlogs as $userlog)
			<tr>
				<td>
					<div class="button-group radius">
						<a href="/staff/user/log/{{ $userlog->id }}/edit" title="Bewerken" class="button tiny">
							<img src="/img/icons/edit.png" alt="Bewerken" />
						</a><a href="/staff/user/log/{{ $userlog->id }}/remove" title="Verwijderen" class="button tiny alert remove confirm">
							<img src="/img/icons/remove.png" alt="Verwijderen" />
						</a>
					</div>
				</td>
				<td>{{ $userlog->user_info->username }}</td>
				<td>{{ $userlog->user_info->schoolnr }}</td>
				<td>{{ $userlog->time }}</td>
				<td><img src="/img/icons/{{ $userlog->nieuw?'validate.png':'reject.png'; }}" alt="" /></td>
				<td>{{ $boekhoudingBetekenis[$userlog->boekhouding]}}</td>
				<td>
					<span class="{{ $userlog->user_info->user->gid < Group::where ('name', 'user')->firstOrFail ()->gid ? 'label' : '' }}">{{ ucfirst ($userlog->user_info->user->getGroup ()->name) }}</span>
				</td>
			</tr>
			@endforeach
		</tbody>
	</table>
	{{ $userlogs->links () }}
	<div class="right">
		<a href="/staff/user/log/create" title="Toevoegen" class="button radius">
			<img src="/img/icons/add.png" alt="Toevoegen" />
		</a>
	</div>
</fieldset>


<div id="modalSearch" class="reveal-modal" data-reveal>
	<h2>Zoeken</h2>

	<form action="{{ $searchUrl }}" method="GET">
		<label>Gebruikersnaam:
			<input type="text" name="username" />
		</label>
		<label>Naam:
			<input type="text" name="name" />
		</label>
		<label>E-mailadres:
			<input type="text" name="email" />
		</label>
		<label>Studentnummer:
			<input type="text" name="schoolnr" />
		</label>
		<label>Van:
			<input type="date" name="time_van" />
		</label>
		<label>Tot:
			<input type="date" name="time_tot" />
		</label>
		<label>Gefactureerd:
			{{ Form::select
				(
					'boekhouding',
					array
					(
						'all' => 'Alles',
						'-1'=>'Niet te factureren',
						'0'=>'Nog te factureren',
						'1'=>'Gefactureerd'
					)
				)
			}}
		</label>
		<label>Nieuw:
			{{ Form::select
				(
					'nieuw',
					array
					(
						'all' => 'Alles',
						'0' => 'Nee',
						'1' => 'Ja',
					)
				)
			}}
		</label>
		<label>Pagination:
			<input type="checkbox" name="pagination" value="true" checked="checked"/>
		</label>
		
		<button>Zoeken</button>
	</form>

	<a class="close-reveal-modal">&#215;</a>
</div>
@endsection