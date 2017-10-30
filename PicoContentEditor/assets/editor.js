var editor = ContentTools.EditorApp.get();
editor.init('[data-editable], [data-fixture]', 'data-name');
editor.addEventListener('saved', function (ev) {
    var name, onStateChange, passive, payload, xhr;

    var edits = ev.detail().regions;
        regions = editor.regions(),
        query = { regions: {} },
        editsNames = Object.keys(edits);

    // Check if this was a passive save
    passive = ev.detail().passive;
    
    // Check to see if there are any changes to save
    if (editsNames.length == 0) return;

    // for fixtures, uses dom innerHTML to get rid of outer el.
    // see https://github.com/GetmeUK/ContentTools/issues/448
    editsNames.forEach(name => {
        var el = regions[name].domElement();
        // this region is the page meta
        if (el.dataset.meta !== undefined) {
            query.meta = el.innerHTML
            // as we don't use proper region value (see above),
            // we need to get rid of utility character
            // see https://github.com/GetmeUK/ContentTools/issues/263
            query.meta = query.meta.replace(/\u200B/g, '');
            delete query.regions[name];
            return;
        }
        if (regions[name].type() == 'Fixture')
            query.regions[name] = el.innerHTML;
        else query.regions[name] = regions[name].html();
    });

    // Set the editors state to busy while we save our changes
    this.busy(true);

    // Collect the contents of each region into a FormData instance
    payload = new FormData();
    payload.append('PicoContentEditor', JSON.stringify(query));

    // Send the update content to the server to be saved
    onStateChange = function(ev) {
        // Check if the request is finished
        if (ev.target.readyState == 4) {
            editor.busy(false);
            if (ev.target.status != '200') {
                // Save failed, notify the user with a flash
                new ContentTools.FlashUI('no');
                return;
            }
            var response;
            try {
                response = JSON.parse(ev.target.response);
            } catch (error) {
                // response error
                new Noty({
                    type: 'error',
                    text: 'There was an error reading the server response',
                    timeout: status.state ? 3000 : 5000
                }).show(); 
                console.log(ev.target.response);
                return;
            }
            if (passive || !response) return;
            console.log(response);
            // response notifications
            response.status.forEach( status => {
                new Noty({
                    type: status.state ? 'success' : 'warning',
                    text: status.message,
                    timeout: status.state ? 3000 : 5000
                }).show(); 
            });
            // debug notifications
            if (response.edited.regions) {
                var i = 0;
                for(var id in response.edited.regions) {
                    var region = response.edited.regions[id],
                        source = region.source ? `(<em>${region.source.split(/[\\/]/).pop()}</em>)` : '';
                    setTimeout(function(){
                        new Noty({
                            type: region.saved ? 'success' : 'error',
                            text: `<strong>Debug</strong><br><em>${id}</em> : ${region.message} ${source}`,
                            timeout: region.saved ? 3000 : 5000
                        }).show();
                    }, 50 * ++i);
                }
            }
        }
    };

    xhr = new XMLHttpRequest();
    xhr.addEventListener('readystatechange', onStateChange);
    xhr.open('POST', '?contentsave');
    xhr.send(payload);
});


//
// Translation
//
var el_lang = document.getElementById('ContentToolsLanguage');
if (el_lang && el_lang.dataset.lang) {
    let translation = JSON.parse(el_lang.textContent);
    ContentEdit.addTranslations(el_lang.dataset.lang, translation);
    ContentEdit.LANGUAGE = el_lang.dataset.lang;
}