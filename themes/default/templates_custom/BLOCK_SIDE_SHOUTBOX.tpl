<div class="boxless_space global_side_panel">
	<div class="box" role="marquee"><div class="box_inner">
		{MESSAGES}
	
		<form target="_self" action="{URL*}&amp;posted=1" method="post" title="{!SHOUTBOX}">
			<div class="constrain_field">
				<p class="accessibility_hidden"><label for="shoutbox_message">{!MESSAGE}</label></p>
				<input value="" type="text" onfocus="if (this.value=='{!MESSAGE;}') this.value='';" id="shoutbox_message" name="shoutbox_message" alt="{!MESSAGE}" class="wide_field" />
			</div>
			<div class="float_surrounder">
				<div style="width: 50%; float: left">
					<div class="constrain_field">
						<input style="margin: 0" onclick="window.top.setTimeout(function() { window.top.sb_chat_check(window.top.sb_last_message_id,-1); }, 2000); if (!check_field_for_blankness(this.form.elements['shoutbox_message'],event)) return false; disable_button_just_clicked(this); return true" type="submit" value="Send &uarr;" class="wide_button" />
					</div>
				</div>
				<div style="width: 50%; float: right">
					<div class="constrain_field">
						<input style="margin: 0" onclick="this.form.elements['shoutbox_message'].value='((SHAKE))'; window.top.setTimeout(function() { window.top.sb_chat_check(window.top.sb_last_message_id,-1); }, 2000); disable_button_just_clicked(this);" type="submit" title="Shake the screen of all active website visitors" value="Shake it!" class="wide_button" />
					</div>
				</div>
			</div>
		</form>
	
		<script type="text/javascript">// <![CDATA[
			document.getElementById('shoutbox_message').setAttribute('autocomplete','off');
		//]]></script>
	</div></div>
</div>
