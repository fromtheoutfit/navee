$(function () {
  var linkType = $('#linkType');

  if (linkType.val().length){
    setLinkType(linkType.val());
  }

  linkType.change(function()
  {
    setLinkType(linkType.val());
  });

  function setLinkType(linkType)
  {
    $('#entryId-field, #assetId-field, #categoryId-field, #customUri-field').hide();
    $('#' + linkType + '-field').show();
  }
});