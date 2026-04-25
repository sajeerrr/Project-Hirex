<?php
// worker-page-end.php — call at bottom of every page
?>
</main>

<!-- TOAST -->
<div class="toast" id="wToast">
    <div class="toast-content">
        <div class="toast-title" id="wToastTitle">Done</div>
        <div class="toast-message" id="wToastMsg"></div>
    </div>
</div>

<script>
function wToggleSidebar(){
    const s=document.getElementById('sidebar'),o=document.getElementById('overlay');
    s.classList.toggle('active');o.classList.toggle('active');
    document.body.style.overflow=s.classList.contains('active')?'hidden':'';
}
document.addEventListener('keydown',e=>{if(e.key==='Escape'){document.getElementById('sidebar').classList.remove('active');document.getElementById('overlay').classList.remove('active');}});

(function(){
    if(localStorage.getItem('wTheme')==='dark'){
        document.documentElement.setAttribute('data-theme','dark');
        const i=document.getElementById('wThemeIcon'),t=document.getElementById('wThemeText');
        if(i)i.innerHTML='<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
        if(t)t.textContent='Light';
    }
})();

function wToggleTheme(){
    const h=document.documentElement,dark=h.getAttribute('data-theme')==='dark';
    const i=document.getElementById('wThemeIcon'),t=document.getElementById('wThemeText');
    const moonSVG='<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
    const sunSVG='<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
    if(dark){h.removeAttribute('data-theme');if(i)i.innerHTML=moonSVG;if(t)t.textContent='Dark';localStorage.setItem('wTheme','light');}
    else{h.setAttribute('data-theme','dark');if(i)i.innerHTML=sunSVG;if(t)t.textContent='Light';localStorage.setItem('wTheme','dark');}
}

function wToast(title,msg,ok=true){
    const t=document.getElementById('wToast');
    document.getElementById('wToastTitle').textContent=title;
    document.getElementById('wToastMsg').textContent=msg||'';
    t.style.borderLeftColor=ok?'var(--success)':'var(--danger)';
    t.classList.add('show');
    setTimeout(()=>t.classList.remove('show'),3200);
}

function wAcceptJob(id){
    fetch('actions/accept_job.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'booking_id='+id})
    .then(r=>r.json()).then(d=>{
        if(d.success){wToast('Accepted!','Job confirmed.',true);const el=document.getElementById('req-'+id);if(el)el.remove();}
        else wToast('Error',d.message||'Failed.',false);
    }).catch(()=>wToast('Error','Network error.',false));
}
function wDeclineJob(id){
    if(!confirm('Decline this job?'))return;
    fetch('actions/reject_job.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'booking_id='+id})
    .then(r=>r.json()).then(d=>{
        if(d.success){wToast('Declined','Request removed.',true);const el=document.getElementById('req-'+id);if(el)el.remove();}
        else wToast('Error',d.message||'Failed.',false);
    }).catch(()=>wToast('Error','Network error.',false));
}

// URL param toasts
(function(){
    const p=new URLSearchParams(window.location.search);
    if(p.get('saved')==='1')wToast('Saved!','Changes saved successfully.',true);
    if(p.get('error')==='1')wToast('Error','Something went wrong.',false);
    if(p.get('avail')==='1')wToast('Updated','Availability status changed.',true);
})();
</script>
<?php if (!empty($extraJS)) echo "<script>{$extraJS}</script>"; ?>
</body>
</html>
