{{-- Modal único de confirmação, incluído uma vez no layout principal. O comportamento está em 'resources/js/confirmacao.js'. --}}
<div class="modal fade" id="modal-confirmacao" tabindex="-1" data-bs-backdrop="static" aria-labelledby="modal-confirmacao-titulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="modal-confirmacao-titulo">Confirmação</h2>
            </div>

            <div class="modal-body">
                <p class="mb-0" data-mensagem></p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-cancelar>Cancelar</button>
                <button type="button" class="btn btn-danger" data-confirmar>Confirmar</button>
            </div>
        </div>
    </div>
</div>