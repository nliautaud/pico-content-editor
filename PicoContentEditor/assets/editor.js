var editor = ContentTools.EditorApp.get();
editor.init('*[data-editable]', 'data-name');

editor.addEventListener('saved', function (ev) {
    var name, onStateChange, passive, payload, regions, xhr;

    // Check if this was a passive save
    passive = ev.detail().passive;

    // Check to see if there are any changes to save
    regions = ev.detail().regions;
    if (Object.keys(regions).length == 0) {
        return;
    }

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
            try {
                var response = JSON.parse(ev.target.response);
                if (passive || !response) return;
                if (response.status == false) {
                    new ContentTools.FlashUI('no');
                } else {
                    // Save was successful, notify the user with a flash
                    new ContentTools.FlashUI('ok');
                }
                console.log(response);
            } catch (error) {
                // response error
                new ContentTools.FlashUI('no');
                console.log(ev.target.response);
                return;
            }
        }
    };

    xhr = new XMLHttpRequest();
    xhr.addEventListener('readystatechange', onStateChange);
    xhr.open('POST', '?contentsave');
    xhr.send(payload);
});