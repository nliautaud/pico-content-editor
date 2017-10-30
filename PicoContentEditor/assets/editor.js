/*global ContentTools ContentEdit Noty*/

(function () {

  var Notify = function (type, text) {
    new Noty({
      type: type,
      text: text,
      timeout: type == 'success' ? 3000 : 5000
    }).show();
  };

  var ImageUploader = function (dialog) {
    var image, xhr, xhrComplete, xhrProgress;

    // Cancel the current upload
    dialog.addEventListener('imageuploader.cancelupload', function () {
      // Stop the upload
      if (xhr) {
        xhr.upload.removeEventListener('progress', xhrProgress);
        xhr.removeEventListener('readystatechange', xhrComplete);
        xhr.abort();
      }
      // Set the dialog to empty
      dialog.state('empty');
    });

    // Clear the current image
    dialog.addEventListener('imageuploader.clear', function () {
      dialog.clear();
      image = null;
    });

    // Handle upload progress and completion
    dialog.addEventListener('imageuploader.fileready', function (ev) {

      function xhrProgress(ev) {
        // Set the progress for the upload
        dialog.progress((ev.loaded / ev.total) * 100);
      }

      function xhrComplete(ev) {
        var response;

        // Check the request is complete
        if (ev.target.readyState != 4) {
          return;
        }

        // Clear the request
        xhr = null
        xhrProgress = null
        xhrComplete = null

        if (parseInt(ev.target.status) != 200) {
          // The request failed, notify the user
          new ContentTools.FlashUI('no');
          return;
        }

        // Handle the result of the upload
        // Unpack the response (from JSON)
        try {
          response = JSON.parse(ev.target.responseText);
        } catch (e) {
          console.log(ev.target.responseText);
          Notify('error', 'There was an error reading the server response');
          return;
        }

        // Store the image details
        image = {
          url: response.file.path,
          name: response.file.name,
          width: parseInt(response.file.size[0]),
          height: parseInt(response.file.size[1])
        };
        image.size = [image.width, image.height];

        // Populate the dialog
        dialog.populate(image.url, image.size);

        // response notifications
        response.status.forEach(status => {
          Notify(status.state ? 'success' : 'warning', status.message);
        });
      }

      // Set the dialog state to uploading and reset the progress bar to 0
      dialog.state('uploading');
      dialog.progress(0);

      // Build the form data to post to the server
      var file = ev.detail().file;
      console.log(file);
      var formData = new FormData();
      formData.append('PicoContentEditorUpload', file);

      // Make the request
      xhr = new XMLHttpRequest();
      xhr.upload.addEventListener('progress', xhrProgress);
      xhr.addEventListener('readystatechange', xhrComplete);
      xhr.open('POST', '', true);
      xhr.send(formData);
    });

    dialog.addEventListener('imageuploader.save', function () {
      var crop, cropRegion, formData;
      // Check if a crop region has been defined by the user
      if (dialog.cropRegion()) {
        crop = dialog.cropRegion()
      }
      // Trigger the save event against the dialog with details of the
      // image to be inserted.
      console.log(image);
      dialog.save(image.url, image.size, {
        'alt': image.name,
        'data-ce-max-width': image.width
      });
    });
  }


  window.onload = function () {

    var editor;
    ContentTools.IMAGE_UPLOADER = ImageUploader;

    let applyToImages = function (element) {
      return element.content !== undefined || element.type() === 'Image' || element.type() === 'Video';
    }
    ContentTools.Tools.AlignLeft.canApply = applyToImages;
    ContentTools.Tools.AlignRight.canApply = applyToImages;
    ContentTools.Tools.AlignCenter.canApply = applyToImages;

    editor = ContentTools.EditorApp.get();
    editor.init('[data-editable], [data-fixture]', 'data-name');

    editor.addEventListener('saved', function (ev) {
      var onStateChange, passive, payload, xhr;

      var edits = ev.detail().regions,
        regions = editor.regions(),
        query = {
          regions: {}
        },
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
      onStateChange = function (ev) {
        // Check if the request is finished
        if (ev.target.readyState == 4) {
          editor.busy(false);
          if (ev.target.status != '200') {
            // Save failed, Notify the user with a flash
            new ContentTools.FlashUI('no');
            return;
          }
          var response;
          try {
            response = JSON.parse(ev.target.response);
          } catch (error) {
            // response error
            Notify('error', 'There was an error reading the server response');
            console.log(ev.target.response);
            return;
          }
          if (passive || !response) return;
          console.log(response);
          // response notifications
          response.status.forEach(status => {
            Notify(status.state ? 'success' : 'warning', status.message);
          });
          // debug notifications
          if (response.edited.regions) {
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
      };

      xhr = new XMLHttpRequest();
      xhr.addEventListener('readystatechange', onStateChange);
      xhr.open('POST', '');
      xhr.send(payload);
    });

    // Translation
    var el_lang = document.getElementById('ContentToolsLanguage');
    if (el_lang && el_lang.dataset.lang) {
      let translation = JSON.parse(el_lang.textContent);
      ContentEdit.addTranslations(el_lang.dataset.lang, translation);
      ContentEdit.LANGUAGE = el_lang.dataset.lang;
    }

    // Contextual tools
    var FIXTURE_TOOLS = [
        ['bold', 'italic', 'link'],
        ['undo', 'redo']
      ],
      IMAGE_FIXTURE_TOOLS = [
        ['undo', 'redo', 'image']
      ],
      PRE_FIXTURE_TOOLS = [
        ['undo', 'redo']
      ];
    ContentEdit.Root.get().bind('focus', function (element) {
      var tools;
      if (element.isFixed()) {
        if (element.tagName() === 'pre') {
          tools = PRE_FIXTURE_TOOLS;
        } else if (element.type() === 'ImageFixture') {
          tools = IMAGE_FIXTURE_TOOLS;
        } else {
          tools = FIXTURE_TOOLS;
        }
      } else {
        tools = ContentTools.DEFAULT_TOOLS;
      }
      if (editor.toolbox().tools() !== tools) {
        return editor.toolbox().tools(tools);
      }
    });
  };

}).call(this);