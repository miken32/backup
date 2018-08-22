$(document).ready(() => {

  $("#runrestore").click(event =>{
    event.preventDefault();
    var fileid = window.fileid;
    $.ajax({
      url: ajaxurl,
      method: "GET",
      data: {
        module: 'backup',
        command: 'runRestore',
        fileid: fileid
      }
    })
    .then(data => {
      console.log(data);
    });
  });
  $("#goback").click(event => {
    event.preventDefault();
    window.history(-1);
  });
  $("#backupUpload").click( event => {
    event.preventDefault();
    let file = $("#filetorestore")[0].files[0];
    var formData = new FormData();
    formData.append('filetorestore', file);
    $.ajax({
      url: `${ajaxurl}?module=backup&command=uploadrestore`,
      type: 'POST',
      data: formData,
      processData: false,  // tell jQuery not to process the data
      contentType: false  // tell jQuery not to set contentType
    })
    .then(data => {
      if(data.status == true){
        let url = `${window.location.href}&view=processrestore&id=${data.id}`;
        console.log(url);
        //window.location = url;
      }else{
        fpbxToast(data.message, 'error');
      }
    })
    .fail(err =>{
      fpbxToast("Unable to upload File");
      console.log(err);
      return false;
    });
    return false;
  });
   var dz = new Dropzone("#uploadrestore",{
    url: `${ajaxurl}?module=backup&command=uploadrestore`,
    chunking: true,
    maxFiles: 1,
    maxFilesize: null,
    previewsContainer: false
  });
  dz.on('success', function(file){
    var ret = file.xhr.response || "{}";
    var jres = JSON.parse(ret);
    if(jres.md5.length){
      window.location = `?display=backup_restore&view=processrestore&type=local&id=${jres.md5}`;
    }

  });
  dz.on('uploadprogress', function(event,progress,total){
    var current = (event.upload.chunks.length / event.upload.totalChunkCount) * 100;
      $("#uploadprogress").css('width', `${current}%`);
  });


});//end document ready

function localLinkFormatter(value, row, index) {
  var html = '<a href="?display=backup_restore&view=processrestore&type=local&id=' + row['id'] + '"><i class="fa fa-play"></i></a>';
  html += '<a href="/admin/api/backup/localdownload?id='+row['id']+'" class="localdownload" target="_blank"><i class="fa fa-download"></i></a>';
  html += '&nbsp;<a href="#" id="' + row['id'] + '" class="localDelete"><i class="fa fa-trash"></i></a>';
  return html;
}
function remoteFormatter(value,row,index){
  var html = `<a href="/admin/api/backup/remotedownload?id=${row['id']}&filepath=${row['file']}" class="remotedownload" target="_blank"><i class="fa fa-download"></i></a>`;
  html += `<a href="?display=backup_restore&view=processrestore&type=remote&id=${row['id']}&filepath=${row['file']}"><i class="fa fa-play"></i></a>`;
  html += `<a href="#" data-id = "${row['id']}" data-file = "${row['file']}" class="remoteDelete delitem"><i class = "fa fa-trash"></i></a>`;

  return html;
}
$("table").on("post-body.bs.table", function () {
  $('.localDelete').on('click', e =>{
    e.preventDefault();
    fpbxConfirm(_("Are you sure you wish to delete this file? This cannot be undone"),
      _("Delete"),_("Cancel"),
      function(){
        var id = e.currentTarget.id;
        $.ajax({
          url: ajaxurl,
          method: "GET",
          data: {
            module: 'backup',
            command: 'deleteLocal',
            id: id
          }
        })
        .then(data => {
          console.log(data);
          if(data.status){
            $("#localrestorefiles").bootstrapTable('refresh',{silent:true});
          }
          fpbxToast(data.message);
        });
      }
    );
  });
});