<input style="display: none" type="text" class="input_line{REQUIRED*} hidden_but_needed" id="{NAME*}" name="{NAME*}" value="{DEFAULT*}" onchange="var ob=document.getElementById('{NAME*;}_mirror'); if (ob) { ob.parentNode.style.display=(this.selected_title=='')?'none':'block'; setInnerHTML(ob,escape_html(this.selected_title)); }" />
<div class="ajax_tree_list" id="tree_list__root_{NAME*}"{$?,{$VALUE_OPTION,html5}, role="tree"}>
	<!-- List put in here -->
</div>
<script type="text/javascript">// <![CDATA[
addEventListenerAbstract(window,'load',function () {
	new tree_list('{NAME*;}','data/ajax_tree.php?hook={HOOK&;}{$KEEP;}','{ROOT_ID&;}','{OPTIONS&;}',false,{TABINDEX%},false{+START,IF,{USE_SERVER_ID}},true{+END});
} );
//]]></script>

<p class="associated_details">
	{!TREE_LIST_HELP}
</p>
{+START,IF_NON_EMPTY,{DEFAULT}}
	<p class="associated_details">
		{!TREE_LIST_FEEDBACK,<span class="whitespace" id="{NAME*}_mirror">{NICE_LABEL`}</span>}
	</p>
{+END}

{+START,IF_NON_PASSED,END_OF_FORM}
<hr />
{+END}
