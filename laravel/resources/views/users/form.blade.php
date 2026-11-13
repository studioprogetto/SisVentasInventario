<label>Nombre completo<input name="nombre_completo" maxlength="100" value="{{ $user?->nombre_completo }}" required></label>
<label>Usuario<input name="username" maxlength="50" value="{{ $user?->username }}" autocomplete="off" required></label>
<label>Rol<select name="id_rol" required>@foreach($roles as $role)<option value="{{ $role->id_rol }}" @selected($user?->id_rol===$role->id_rol)>{{ $role->nombre_rol }}</option>@endforeach</select></label>
<label>Contraseña {{ $user ? '(dejar vacía para conservar)' : '' }}<input type="password" name="password" minlength="8" maxlength="255" autocomplete="new-password" @required(!$user)></label>
<label>Confirmar contraseña<input type="password" name="password_confirmation" minlength="8" maxlength="255" autocomplete="new-password" @required(!$user)></label>
