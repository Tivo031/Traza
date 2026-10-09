@csrf
<input type="hidden" name="id_columna_esperada" value="{{ $tarea->id_columna }}">
<input type="hidden" name="revision_esperada" value="{{ \App\Support\VistaTarea::revision($tarea) }}">
<input type="hidden" name="formulario" value="{{ $formulario }}">
