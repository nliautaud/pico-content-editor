
var Notify = function (type, text) {
    new Noty({
        type: type,
        text: text,
        timeout: type == 'success' ? 3000 : 5000
    }).show();
};

class ContentEditor {
    constructor(rootElement) {
        var Delta = Quill.import('delta');

        this.editors = [];
        var editables = rootElement.querySelectorAll('*[data-editable], *[data-meta]');
        for (let i=0, el; el = editables[i]; i++) {
            this.editors.push(new Editor(el));    
        }

        // Save periodically
        setTimeout(() => {
            this.save();
        }, 5*1000);

        // Check for unsaved data
        window.onbeforeunload = function() {
            if (change.length() > 0) {
                return 'There are unsaved changes. Are you sure you want to leave?';
            }
        }
    }

    get changedEditors() {
        return this.editors.filter(e => e.haveChanged);
    }
    
    save() {
        if (this.changedEditors.length == 0) {
            return;
        }
        let query = {
            regions: this.changedEditors.map(e => {
                return {id: e.id, deltas: e.deltas, html: e.html};
            })
        };
        let payload = new FormData();
        payload.append('PicoContentEditor', JSON.stringify(query));

        let xhr = new XMLHttpRequest();
        xhr.addEventListener('readystatechange', ev => {
            if (ev.target.readyState == 4) {
                this.onSaveResponse(ev.target.status, ev.target.response);
            }
        });
        xhr.open('POST', '');
        xhr.send(payload);
        
        console.log('PicoContentEditor : SAVE', query);
    }

    onSaveResponse(status, response) {
        if (status != '200') {
            // Save failed, Notify the user with a flash
            new ContentTools.FlashUI('no');
            return;
        }
        try {
            response = JSON.parse(response);
        } catch (error) {
            // response error
            Notify('error', 'There was an error reading the server response');
            console.error('PicoContentEditor : SAVE ERROR', response);
            return;
        }
        if (!response) return;

        //if (response.debug) {
            console.log('PicoContentEditor : SAVE RESPONSE', response);
        //}

        if(status.state == 'success') {
            this.changedEditors.forEach(e => e.saved());
        }

        // response notifications
        response.status.forEach(status => {
            Notify(status.state ? 'success' : 'warning', status.message);
        });

        // debug notifications
        if (response.debug && response.edited.regions) {
            var i = 0;
            for (var id in response.edited.regions) {
            var region = response.edited.regions[id],
                source = region.source ? `(<em>${region.source.split(/[\\/]/).pop()}</em>)` : '';
            setTimeout(function () {
                Notify(
                region.saved ? 'success' : 'error',
                `<strong>Debug</strong><br><em>${id}</em> : ${region.message} ${source}`
                );
            }, 50 * ++i);
            }
        }
    }
}

class Editor {
    constructor(el) {
        this.element = el;
        this.id = el.dataset.name;
        this.isInline = el.dataset.inline;

        const inlineToolbar = [
            ['bold', 'italic', 'underline']
        ];
        const blockToolbar = [
            [{ header: [1, 2, false] }],
            ['bold', 'italic', 'underline'],
            ['image', 'code-block']
        ];
        
        this.quill = new Quill(el, {
            bounds: el,
            modules: {
                toolbar: this.isInline ? inlineToolbar : blockToolbar,
                imageResize: {
                    displaySize: true
                }
            },
            placeholder: el.dataset.placeholder,
            theme: 'bubble'
        });

        this.change = new Delta();
        this.quill.on('text-change', delta => {
            this.change = this.change.compose(delta);
        });
    }

    get haveChanged() {
        return this.change.length() > 0;
    }
    get deltas() {
        return this.quill.getContents();
    }
    get html() {
        return this.quill.root.innerHTML;
    }
    get changes() {
        return this.change;
    }

    saved() {
        this.change = new Delta();
    }
}


var Delta = Quill.import('delta');
new ContentEditor(document);