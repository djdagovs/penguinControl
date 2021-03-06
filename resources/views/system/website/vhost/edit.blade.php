@extends ('layout.master')

@section ('pageTitle')
Edit vHost
@endsection

@section ('content')
<form action="/website/vhost/{{ $vhost->id }}/edit" method="POST" data-abide>
	<fieldset>
		<legend>Edit vHost</legend>
		<div class="row">
			<div class="large-4 medium-12 small-12 column">
				<label>Host:
					<input type="text" name="servername" value="{{ Input::old ('servername', $vhost->servername) }}" required disabled/>
				</label>
				<small class="error">Required field</small>
			</div>
			<div class="large-8 medium-12 small-12 column">
				<label>Document root:
					<div class="row collapse">
						<div class="large-5 medium-6 small-12 column">
							<span class="prefix">{{ $insideHomedir ? $vhost->user->homedir : '' }}/</span>
						</div>
						<div class="large-7 medium-6 small-12 column">
							<input type="text" name="docroot" {{ $insideHomedir ? '' : 'disabled' }} value="{{ Input::old ('docroot', substr ($vhost->docroot, ($insideHomedir ? strlen ($vhost->user->homedir): 0) + 1)) }}" />
						</div>
					</div>
				</label>
				<small class="error">Invalid input</small>
			</div>
		</div>
		<div class="row">
			<div class="large-4 medium-12 small-12 column">
				<label>Administrator:
					<input type="email" name="serveradmin" value="{{ Input::old ('serveradmin', $vhost->serveradmin) }}" required disabled />
				</label>
				<small class="error">Required field</small>
			</div>
			<div class="large-8 medium-12 small-12 column">
				<label>Aliases:
					<small>(Separate multiple aliases with spaces.)</small>
					<input type="text" name="serveralias" value="{{ Input::old ('serveralias', $vhost->serveralias) }}" />
				</label>
				<small class="error">Invalid input</small>
			</div>
		</div>
		<div class="row">
			<div class="large-6 medium-7 small-12 column">
				<label>Protocol:
					{{ Form::select
					(
						'ssl',
						array
						(
							'0' => 'HTTP',
							'1' => 'HTTP + HTTPS',
							'2' => 'HTTPS with redirect'
						),
						Input::old ('ssl', $vhost->ssl)
					)
					}}
				</label>
				<small class="error">Invalid input</small>
			</div>
			<div class="large-6 medium-5 small-12 column">
				<label>CGI:
					{{ Form::select
					(
						'cgi',
						array
						(
							'0' => 'Off',
							'1' => 'On'
						),
						Input::old ('cgi', $vhost->cgi)
					)
					}}
				</label>
				<small class="error">Invalid input</small>
			</div>
		</div>
		@section ('custom_fields')
		@show
		<div>
			{{ Form::token () }}
			{{ $insideHomedir ? '' : Form::hidden ('outsideHomedir', 'true') }}
			<button name="save" value="{{ $vhost->id }}">Save</button>
		</div>
	</fieldset>
</form>
@endsection