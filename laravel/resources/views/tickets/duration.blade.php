<label>Duración del QR
    <select name="segundos">
        @foreach([10,30,60,120,0] as $duration)
            <option value="{{ $duration }}" @selected($duration === $seconds)>{{ $duration ? $duration.' s' : 'Manual' }}</option>
        @endforeach
    </select>
</label>
