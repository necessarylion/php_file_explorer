	<!-- MODALs -->
	<div id="uploadModal" class="modal">
		<button class="btn flat" style="float: right;" onclick="modal('off');">Close</button>
		<h5 class="title">Upload Files</h5>
		<div id="drop_area">
			<div class="inputs">
				<p>Drop Files Here</p>
				<p>or</p>
				<label for="uploadfile" class="btn">Choose Files</label>
				<input id="uploadfile" type="file" multiple>
				<p class="small">Maximum Upload File Size: &nbsp; <b class="maxSize"><?= $max_upload_size; ?></b></p>
			</div>
		</div>
	</div>

	<div id="progressModal" class="modal">
		<h4 class="title">Uploading</h4>
		<ul class="body"></ul>
		<div class="action">
			<button type="button" class="btn">Abort</button>
		</div>
	</div>

	<div id="newDirModal" class="modal">
		<h5 class="title">Create New Folder</h5>
		<form >
			<div class="inputs">
				<label>Enter Directory Name<input id="dirname" type="text"></label>
			</div>
			<div class="action">
				<button type="submit" class="btn">Create</button>
				<button type="button" class="btn flat" onclick="modal('off');">Close</button>
			</div>
		</form>
	</div>

	<div id="newFileModal" class="modal">
		<h5 class="title">Create New File</h5>
		<form>
			<div class="inputs">
				<label>Enter File Name<input id="filename" type="text"></label>
			</div>
			<div class="action">
				<button type="submit" class="btn">Create</button>
				<button type="button" class="btn flat" onclick="modal('off');">Close</button>
			</div>
		</form>
	</div>

	<div id="renameModal" class="modal">
		<h5 class="title">Rename</h5>
		<form>
			<input type="hidden" id="path" />
			<div class="inputs">
				<label>Enter New Name<input id="newname" type="text"></label>
			</div>
			<div class="action">
				<button type="submit" class="btn">Rename</button>
				<button type="button" class="btn flat" onclick="modal('off');">Close</button>
			</div>
		</form>
	</div>

	<div id="permitModal" class="modal">
		<h5 class="title">Set Permission</h5>
		<div class="inputs inline" title="Owner Permissions">
			<label class="inline"><input type="checkbox" id="ownRead">Read</label>
			<label class="inline"><input type="checkbox" id="ownWrit">Write</label>
			<label class="inline"><input type="checkbox" id="ownExec">Execute</label>
		</div>
		<div class="inputs inline" title="Group Permissions">
			<label class="inline"><input type="checkbox" id="grpRead">Read</label>
			<label class="inline"><input type="checkbox" id="grpWrit">Write</label>
			<label class="inline"><input type="checkbox" id="grpExec">Execute</label>
		</div>
		<div class="inputs inline" title="Public Permissions">
			<label class="inline"><input type="checkbox" id="pubRead">Read</label>
			<label class="inline"><input type="checkbox" id="pubWrit">Write</label>
			<label class="inline"><input type="checkbox" id="pubExec">Execute</label>
		</div>
		<form>
			<input type="hidden" id="perm_path" name="perm_path">
			<div class="inputs"><label>Permission<input id="perm_code" type="text" maxlength="4" pattern="^0[0-7][0-7][0-7]$"></label></div>
			<div class="inputs recurse"><label class="inline"><input type="checkbox" id="perm_recursive_chk">Recurse into All Sub-Directories</label></div>
			<div class="inputs recurse"><label class="inline"><input type="radio" name="recurse" value="df" disabled>All Files & Directories</label></div>
			<div class="inputs recurse"><label class="inline"><input type="radio" name="recurse" value="d"  disabled>Directories Only</label></div>
			<div class="inputs recurse"><label class="inline"><input type="radio" name="recurse" value="f"  disabled>Files Only</label></div>
			<div class="action">
				<button type="submit" class="btn">Modify</button>
				<button type="button" class="btn flat" onclick="modal('off');">Close</button>
			</div>
		</form>
	</div>

	<div id="detailModal" class="modal">
		<h5 class="title">Details and Info</h5>
		<div class="inputs"><span>Name</span><b class="name"></b></div>
		<div class="inputs"><span>Path</span><b class="path"></b></div>
		<div class="inputs"><span>Size</span><b class="size"></b></div>
		<div class="inputs"><span>Type</span><b class="type"></b></div>
		<div class="inputs"><span>Owner</span><b class="ownr"></b></div>
		<div class="inputs"><span>Permission</span><b class="perm"></b></div>
		<div class="inputs"><span>Created Time</span><b class="ctime"></b></div>
		<div class="inputs"><span>Accessed Time</span><b class="atime"></b></div>
		<div class="inputs"><span>Modified Time</span><b class="mtime"></b></div>
		<div class="action">
			<button type="button" class="btn" onclick="copy(document.querySelector('#detailModal b.path').innerHTML); toast('Copied to clipboard')">Copy Path</button>
			<button type="button" class="btn flat" onclick="modal('off');">Close</button>
		</div>
	</div>
	<!-- MODALs END -->