{include file="header.tpl"}
<h2>{$title}</h2>

{if $type == 'C'}
<form action="docadd.php" method="post" name="docform">
<input type="hidden" name="eid" value="{$eid}" />
<input type="hidden" name="type" value="C" />

<table>
  <tr>
	  <td class="alignT"><label for="description">__Subject__:</label></td>
    <td><input type="text" name="description" size="50" maxlength="127" /></td>
	</tr>
<!-- TODO: htmlarea or fckeditor support -->
  <tr>
	  <td class="alignT"><label for="comment">__Comment__:</label></td>
    <td><textarea name="comment" rows="15" cols="60" wrap="auto"></textarea></td>
	</tr>
  <tr>
	  <td colspan="2">
<input type="submit" value="__Add Comment__" /></td>
  </tr>
</table>
</form>


{else if $type == 'A'}
<form action="docadd.php" method="post" name="docform" enctype="multipart/form-data"> 
  <input type="hidden" name="eid" value="{$eid}" />
  <input type="hidden" name="type" value="A" />
<table>
  <tr class="browse">
	  <td><label for="fileupload">__Upload file__:</label></td>
		<td><input type="file" name="FileName" id="fileupload" size="45" maxlength="50" />
		</td>
	</tr>
  <tr>
	  <td class="alignT"><label for="description">__Description__:</label></td>
    <td><input type="text" name="description" size="50" maxlength="127" /></td>
	</tr>
  <tr>
	  <td colspan="2">
      <input type="submit" value="__Add Attachment__" /></td>
	</tr>
</table>
</form>
{/if}

{if $use_fckeditor}
<script type="text/javascript" src="includes/FCKeditor-2.0/fckeditor.js"></script>
<script type="text/javascript">
   var myFCKeditor = new FCKeditor( 'comment' );
   myFCKeditor.BasePath = 'includes/FCKeditor-2.0/';
   myFCKeditor.ToolbarSet = 'Medium';
   myFCKeditor.Config['SkinPath'] = './skins/office2003/';
   myFCKeditor.ReplaceTextarea();
</script>
{/if}

{include file="footer.tpl"}
