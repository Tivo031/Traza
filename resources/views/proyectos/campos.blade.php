<div class="mb-3">
    <label class="form-label" for="nombre">Nombre <span aria-hidden="true">*</span></label>
    <input id="nombre" name="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre', $registro->nombre) }}" required maxlength="150" autofocus aria-describedby="ayuda-nombre error-nombre">
    <div class="form-text" id="ayuda-nombre">Obligatorio. Máximo 150 caracteres.</div>
    @error('nombre')<div class="invalid-feedback" id="error-nombre">{{ $message }}</div>@enderror
</div>
<div>
    <label class="form-label" for="descripcion">Descripción</label>
    <textarea id="descripcion" name="descripcion" rows="5" maxlength="10000" class="form-control @error('descripcion') is-invalid @enderror" aria-describedby="ayuda-descripcion error-descripcion">{{ old('descripcion', $registro->descripcion) }}</textarea>
    <div class="form-text" id="ayuda-descripcion">Opcional. Máximo 10 000 caracteres.</div>
    @error('descripcion')<div class="invalid-feedback" id="error-descripcion">{{ $message }}</div>@enderror
</div>
