<%-- The "save to list" popup behaviour (progressive enhancement), split out so it can be overridden on its
     own. It drives the markup/data rendered by WishlistButton.ss against the controller's JSON endpoints
     (add / remove / createlist / lists). To integrate without replacing it, listen for the DOM events it
     dispatches on the .wishlist-button element (they bubble): "wishlist:opened" and "wishlist:changed"
     (event.detail = { action, inAny, lists, listId }). To replace it, override this template; to drop it,
     set WishlistController.enable_popup = false. --%>
<script>
(function(){
    var root = document.currentScript.closest('.wishlist-button');
    if(!root || root.getAttribute('data-wl-ready')) return;
    root.setAttribute('data-wl-ready','1');

    var base = root.getAttribute('data-base');
    var view = root.getAttribute('data-view');
    var product = root.getAttribute('data-product');
    var form = root.querySelector('form');
    var btn = root.querySelector('.wishlist-button__btn');
    var tokenEl = form.querySelector('input[name="SecurityID"]');
    var token = tokenEl ? tokenEl.value : null;
    var overlay = null;

    function t(k){ return root.getAttribute('data-i18n-'+k) || ''; }
    function emit(name, detail){ root.dispatchEvent(new CustomEvent(name, {bubbles:true, detail:detail})); }

    function body(obj){
        var p = new URLSearchParams();
        for(var k in obj){ if(obj[k] != null) p.append(k, obj[k]); }
        if(token) p.append('SecurityID', token);
        return p.toString();
    }
    function post(action, data){
        return fetch(base+'/'+action, {
            method:'POST', credentials:'same-origin',
            headers:{'X-Requested-With':'XMLHttpRequest','Content-Type':'application/x-www-form-urlencoded'},
            body: body(data)
        }).then(function(r){ return r.json(); });
    }
    function getLists(){
        return fetch(base+'/lists?ProductID='+encodeURIComponent(product), {
            credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest'}
        }).then(function(r){ return r.json(); });
    }
    function setHeart(inAny){
        btn.setAttribute('data-saved', inAny ? '1' : '0');
        btn.classList.toggle('is-saved', !!inAny);
        btn.textContent = (inAny ? '❤ '+t('saved') : '♡ '+t('save'));
    }
    function esc(e){ if(e.key === 'Escape') close(); }
    function close(){
        if(overlay){ overlay.remove(); overlay = null; document.removeEventListener('keydown', esc); }
    }
    function renderLists(wrap, lists){
        wrap.textContent = '';
        lists.forEach(function(l){
            var row = document.createElement('label'); row.className = 'wl-row';
            var cb = document.createElement('input'); cb.type = 'checkbox'; cb.checked = !!l.contains;
            cb.addEventListener('change', function(){
                cb.disabled = true;
                var action = cb.checked ? 'add' : 'remove';
                post(action, {ProductID:product, WishlistID:l.id}).then(function(res){
                    cb.disabled = false;
                    if(res && res.lists){
                        renderLists(wrap, res.lists); setHeart(res.inAny);
                        emit('wishlist:changed', {action:action, listId:l.id, inAny:res.inAny, lists:res.lists});
                    }
                }).catch(function(){ cb.disabled = false; cb.checked = !cb.checked; });
            });
            var span = document.createElement('span'); span.textContent = l.title;
            row.appendChild(cb); row.appendChild(span); wrap.appendChild(row);
        });
    }
    function open(payload, justAdded){
        close();
        overlay = document.createElement('div'); overlay.className = 'wl-overlay';
        var panel = document.createElement('div'); panel.className = 'wl-panel';
        panel.setAttribute('role','dialog'); panel.setAttribute('aria-modal','true');

        var head = document.createElement('div'); head.className = 'wl-head';
        var h = document.createElement('h2'); h.className = 'wl-title'; h.textContent = t('heading');
        var x = document.createElement('button'); x.type = 'button'; x.className = 'wl-x';
        x.setAttribute('aria-label', t('close')); x.innerHTML = '&times;';
        x.addEventListener('click', close);
        head.appendChild(h); head.appendChild(x); panel.appendChild(head);

        if(justAdded){
            var ok = document.createElement('div'); ok.className = 'wl-added';
            ok.textContent = '✓ '+t('added'); panel.appendChild(ok);
        }

        var wrap = document.createElement('div'); wrap.className = 'wl-lists';
        renderLists(wrap, payload.lists || []); panel.appendChild(wrap);

        var newRow = document.createElement('div'); newRow.className = 'wl-new';
        var addBtn = document.createElement('button'); addBtn.type = 'button'; addBtn.className = 'wl-newbtn';
        addBtn.textContent = '+ '+t('newlist');
        var nf = document.createElement('div'); nf.className = 'wl-newform'; nf.hidden = true;
        var inp = document.createElement('input'); inp.type = 'text'; inp.placeholder = t('listname');
        var crt = document.createElement('button'); crt.type = 'button'; crt.className = 'wl-create'; crt.textContent = t('create');
        addBtn.addEventListener('click', function(){ nf.hidden = false; addBtn.hidden = true; inp.focus(); });
        function doCreate(){
            crt.disabled = true;
            post('createlist', {Title: inp.value.replace(/^\s+|\s+$/g,''), ProductID:product}).then(function(res){
                crt.disabled = false;
                if(res && res.lists){
                    renderLists(wrap, res.lists); setHeart(res.inAny);
                    inp.value=''; nf.hidden = true; addBtn.hidden = false;
                    emit('wishlist:changed', {action:'create', inAny:res.inAny, lists:res.lists});
                }
            }).catch(function(){ crt.disabled = false; });
        }
        crt.addEventListener('click', doCreate);
        inp.addEventListener('keydown', function(e){ if(e.key === 'Enter'){ e.preventDefault(); doCreate(); } });
        nf.appendChild(inp); nf.appendChild(crt);
        newRow.appendChild(addBtn); newRow.appendChild(nf); panel.appendChild(newRow);

        var foot = document.createElement('div'); foot.className = 'wl-foot';
        var done = document.createElement('button'); done.type = 'button'; done.className = 'wl-done';
        done.textContent = t('done'); done.addEventListener('click', close); foot.appendChild(done);
        if(view){ var a = document.createElement('a'); a.className = 'wl-view'; a.href = view; a.textContent = t('view'); foot.appendChild(a); }
        panel.appendChild(foot);

        overlay.appendChild(panel);
        overlay.addEventListener('click', function(e){ if(e.target === overlay) close(); });
        document.addEventListener('keydown', esc);
        document.body.appendChild(overlay);
        var first = panel.querySelector('input,button'); if(first) first.focus();
        emit('wishlist:opened', {payload:payload, justAdded:!!justAdded});
    }

    form.addEventListener('submit', function(e){
        e.preventDefault();
        var saved = btn.getAttribute('data-saved') === '1';
        btn.disabled = true;
        (saved ? getLists() : post('add', {ProductID:product})).then(function(res){
            btn.disabled = false;
            if(!res || !res.success){ form.submit(); return; }
            setHeart(res.inAny);
            if(!saved){ emit('wishlist:changed', {action:'add', inAny:res.inAny, lists:res.lists}); }
            open(res, !saved);
        }).catch(function(){ btn.disabled = false; form.submit(); });
    });
})();
</script>
