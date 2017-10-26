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
            if (ev.target.status == '200') {
                // Save was successful, notify the user with a flash
                if (!passive) {
                    new ContentTools.FlashUI('ok');
                    try {
                        console.log(JSON.parse(ev.target.response));
                    } catch (error) {
                        console.log(ev.target.response);
                    }
                }
            } else {
                // Save failed, notify the user with a flash
                new ContentTools.FlashUI('no');
            }
        }
    };

    xhr = new XMLHttpRequest();
    xhr.addEventListener('readystatechange', onStateChange);
    xhr.open('POST', '?contentsave');
    xhr.send(payload);
});