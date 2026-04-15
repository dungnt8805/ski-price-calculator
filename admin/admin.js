(function(){
    'use strict';

    function showToast(type, message){
        if(!message){
            return;
        }

        var wrap = document.querySelector('.spcu-toast-stack');
        if(!wrap){
            wrap = document.createElement('div');
            wrap.className = 'spcu-toast-stack';
            document.body.appendChild(wrap);
        }

        var toast = document.createElement('div');
        toast.className = 'spcu-toast ' + (type === 'error' ? 'is-error' : 'is-success');
        toast.textContent = message;
        wrap.appendChild(toast);

        requestAnimationFrame(function(){
            toast.classList.add('is-visible');
        });

        setTimeout(function(){
            toast.classList.remove('is-visible');
            setTimeout(function(){
                if(toast.parentNode){
                    toast.parentNode.removeChild(toast);
                }
            }, 220);
        }, 3200);
    }

    function bootToasts(){
        var params = new URLSearchParams(window.location.search);
        var t = params.get('spcu_toast');
        var m = params.get('spcu_msg');
        if(t && m){
            try {
                showToast(t, decodeURIComponent(m));
            } catch (e) {
                showToast(t, m);
            }

            params.delete('spcu_toast');
            params.delete('spcu_msg');
            var query = params.toString();
            var nextUrl = window.location.pathname + (query ? ('?' + query) : '');
            window.history.replaceState({}, document.title, nextUrl);
        }

        document.querySelectorAll('.spcu-toast-source').forEach(function(node){
            var type = node.getAttribute('data-type') || 'success';
            var message = node.getAttribute('data-message') || '';
            showToast(type, message);
        });
    }

    bootToasts();

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
