(() => {
    if (window.EdlinkEngagement) return;
    let recordedPath = '';
    const container = () => document.querySelector('[data-public-engagement]');
    const csrf = () => document.querySelector('meta[name="edlink-engagement-csrf"]')?.content || '';
    async function post(url, body) {
        const response = await fetch(url, {method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify(body)});
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const fallback = response.status === 419 ? 'Your session expired. Refresh this page and try again.'
                : response.status === 429 ? 'Too many requests. Please wait a minute and try again.'
                : response.status === 404 ? 'This website endpoint is unavailable. Please contact Edlink support.'
                : 'Unable to submit (HTTP '+response.status+'). Please refresh the page or use our WhatsApp/email support links.';
            throw new Error(Object.values(data.errors || {}).flat().join(' ') || (response.status < 500 ? data.message : '') || fallback);
        }
        return data;
    }
    async function record() {
        const root = container(), path = location.pathname;
        if (!root || root.dataset.choice !== 'analytics' || !['/','/register','/login'].includes(path) || recordedPath === path) return;
        recordedPath = path;
        const eventKey = crypto.randomUUID ? crypto.randomUUID() : '10000000-1000-4000-8000-100000000000'.replace(/[018]/g,c=>(c^crypto.getRandomValues(new Uint8Array(1))[0]&15>>c/4).toString(16));
        try { await post(root.dataset.visitUrl,{path,event_key:eventKey,referrer:document.referrer.slice(0,1000)}); } catch { recordedPath=''; }
    }
    function init() {
        const root = container();
        if (root) {
            root.querySelector('[data-cookie-banner]').hidden = Boolean(root.dataset.choice);
            root.querySelector('[data-privacy-preferences]').setAttribute('aria-expanded',String(!root.dataset.choice));
            record();
        }
        document.querySelectorAll('[data-public-enquiry]').forEach(updateRequired);
    }
    function updateRequired(form) {
        form.elements.email.required = form.elements.contact_method.value === 'email';
        form.elements.phone.required = form.elements.contact_method.value !== 'email';
    }
    document.addEventListener('change',event=>{
        if (event.target.matches('[data-public-enquiry] [name="contact_method"]')) updateRequired(event.target.form);
    });
    document.addEventListener('click',async event=>{
        const preferences=event.target.closest?.('[data-privacy-preferences]');
        const root=container();
        if (preferences && root) { root.querySelector('[data-cookie-banner]').hidden=false; preferences.setAttribute('aria-expanded','true'); return; }
        const choice=event.target.closest?.('[data-consent]');
        if (!choice || !root) return;
        const buttons=[...root.querySelectorAll('[data-consent]')], status=root.querySelector('[data-cookie-status]');
        buttons.forEach(b=>b.disabled=true); status.textContent='Saving your choice…';
        try {
            const accepted=choice.dataset.consent==='accept';
            await post(root.dataset.consentUrl,{analytics:accepted});
            root.dataset.choice=accepted?'analytics':'essential';
            root.querySelector('[data-cookie-banner]').hidden=true;
            root.querySelector('[data-privacy-preferences]').setAttribute('aria-expanded','false');
            status.textContent=''; recordedPath=''; if(accepted) await record();
        } catch(error) { status.textContent=error.message; }
        finally { buttons.forEach(b=>b.disabled=false); }
    });
    document.addEventListener('submit',async event=>{
        const form=event.target;
        if (!form.matches('[data-public-enquiry]')) return;
        event.preventDefault();
        const button=form.querySelector('[type="submit"]'), status=form.querySelector('[data-enquiry-status]');
        if(button.disabled) return;
        button.disabled=true; status.textContent='Sending your request…';
        try {
            const data=await post(form.action,Object.fromEntries(new FormData(form)));
            status.textContent=data.message;
            form.reset(); updateRequired(form);
        } catch(error) { status.textContent=error.message; }
        finally { button.disabled=false; }
    });
    window.EdlinkEngagement={init};
    if (document.readyState==='loading') document.addEventListener('DOMContentLoaded',init,{once:true}); else init();
    document.addEventListener('livewire:navigated',init);
})();
