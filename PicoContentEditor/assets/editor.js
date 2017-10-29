var editor = ContentTools.EditorApp.get();
editor.init(
    '*[data-editable], [data-fixture]',
    'data-name'
);

editor.addEventListener('saved', function (ev) {
    var name, onStateChange, passive, payload, regions, xhr;

    // Check if this was a passive save
    passive = ev.detail().passive;

    // Check to see if there are any changes to save
    regions = ev.detail().regions;
    if (Object.keys(regions).length == 0) {
        return;
    }

    // keep only inner content of fixtures
    var theregions = editor.regions();
    Object.keys(theregions).forEach(name => {
        if (theregions[name].type() == 'Fixture')
            regions[name] = theregions[name].domElement().innerHTML;
    });

    // Set the editors state to busy while we save our changes
    this.busy(true);

    // Collect the contents of each region into a FormData instance
    payload = new FormData();
    payload.append('PicoContentEditor', JSON.stringify(regions));

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
            if (response.regions) {
                var i = 0;
                for(var id in response.regions) {
                    var region = response.regions[id],
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