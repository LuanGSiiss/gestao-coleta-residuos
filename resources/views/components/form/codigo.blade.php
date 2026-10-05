@props(['valor' => null])

<div class="row">
    <div class="col-sm-4 col-md-3">
        <div class="mb-3">
            <label for="codigo" class="form-label">Código</label>
            <input type="text" id="codigo" class="form-control" value="{{ $valor }}" placeholder="0000" disabled>
        </div>
    </div>
</div>