import { Modal } from 'bootstrap';

/**
 * Confirmação estilizada para formulários marcados com data-confirmacao.
 * Funciona da com um ouvinte no documento que intercepta o envio de qualquer formulário marcado, em qualquer tela. 
 */
document.addEventListener('DOMContentLoaded', () => {
    const elemento = document.getElementById('modal-confirmacao');

    // Telas sem o layout principal, como o login, não têm o modal.
    if (!elemento) {
        return;
    }

    const modal = new Modal(elemento);
    const mensagem = elemento.querySelector('[data-mensagem]');
    const confirmar = elemento.querySelector('[data-confirmar]');
    const cancelar = elemento.querySelector('[data-cancelar]');

    let formularioPendente = null;

    document.addEventListener('submit', (evento) => {
        const formulario = evento.target;

        if (!(formulario instanceof HTMLFormElement) || !formulario.dataset.confirmacao) {
            return;
        }

        evento.preventDefault();
        formularioPendente = formulario;

        mensagem.textContent = formulario.dataset.confirmacao;
        confirmar.textContent = formulario.dataset.confirmacaoAcao || 'Confirmar';
        confirmar.className = `btn btn-${formulario.dataset.confirmacaoEstilo || 'danger'}`;
        confirmar.disabled = false;

        modal.show();
    });

    // Faz com que o foco inicial seja no Cancelar.
    elemento.addEventListener('shown.bs.modal', () => cancelar.focus());

    confirmar.addEventListener('click', () => {
        if (!formularioPendente) {
            return;
        }
        
        confirmar.disabled = true;
        formularioPendente.submit();
    });

    elemento.addEventListener('hidden.bs.modal', () => {
        formularioPendente = null;
    });
});