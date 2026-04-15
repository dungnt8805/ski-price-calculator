(function(){
    'use strict';

    var modal = null;
    var modalText = null;
    var confirmBtn = null;
    var cancelBtn = null;
    var pendingHref = '';

    function ensureModal(){
        if(modal){
            return;
        }

        modal = document.createElement('div');
        modal.className = 'spcu-modal-backdrop';
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = ''
            + '<div class="spcu-modal" role="dialog" aria-modal="true" aria-labelledby="spcu-modal-title">'
            + '  <h2 id="spcu-modal-title">Confirm Deletion</h2>'
            + '  <p class="spcu-modal-message">Are you sure you want to delete this item?</p>'
            + '  <div class="spcu-modal-actions">'
            + '    <button type="button" class="button" data-role="cancel">Cancel</button>'
            + '    <button type="button" class="button button-primary" data-role="confirm">Delete</button>'
            + '  </div>'
            + '</div>';

        document.body.appendChild(modal);

        modalText = modal.querySelector('.spcu-modal-message');
        confirmBtn = modal.querySelector('[data-role="confirm"]');
        cancelBtn = modal.querySelector('[data-role="cancel"]');

        confirmBtn.addEventListener('click', function(){
            if(pendingHref){
                window.location.href = pendingHref;
            }
        });

        cancelBtn.addEventListener('click', closeModal);

        modal.addEventListener('click', function(e){
            if(e.target === modal){
                closeModal();
            }
        });

        document.addEventListener('keydown', function(e){
            if(e.key === 'Escape' && modal.classList.contains('is-open')){
                closeModal();
            }
        });
    }

    function openModal(message, href){
        ensureModal();
        pendingHref = href;
        modalText.textContent = message;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        confirmBtn.focus();
    }

    function closeModal(){
        if(!modal){
            return;
        }
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        pendingHref = '';
    }

    document.addEventListener('click', function(e){
        var link = e.target.closest('a.spcu-delete');
        if(!link){
            return;
        }

        e.preventDefault();
        var message = link.getAttribute('data-confirm') || 'Are you sure you want to delete this item?';
        openModal(message, link.href);
    });
})();
