{+START,IF_NON_PASSED_OR_FALSE,WYSIWYG_SAFE}
	{+START,IF_EMPTY,{$META_DATA,image}}
		{$META_DATA,image,{SCRIPT}?id={ID}{SUP_PARAMS}{$KEEP,0,1}&thumb=0&for_session={$SESSION_HASHED}&no_count=1}
	{+END}
{+END}

{+START,IF,{$EQ,{A_THUMB},1}}<a rel="lightbox" target="_blank" title="{A_DESCRIPTION*} {!LINK_NEW_WINDOW}" href="{SCRIPT*}?id={ID*}{+START,IF_PASSED,SUP_PARAMS}{SUP_PARAMS*}{+END}{+START,IF_NON_PASSED_OR_FALSE,WYSIWYG_SAFE}{$KEEP*,0,1}&amp;for_session={$SESSION_HASHED*}{+END}">{+END}<img class="attachment_img" src="{SCRIPT*}?id={ID*}{+START,IF_PASSED,SUP_PARAMS}{SUP_PARAMS*}{+END}&amp;thumb={A_THUMB*}{+START,IF_NON_PASSED_OR_FALSE,WYSIWYG_SAFE}{$KEEP*,0,1}&amp;for_session={$SESSION_HASHED*}{+END}"{+START,IF_NON_PASSED_OR_FALSE,WYSIWYG_SAFE}{+START,IF,{$EQ,{A_THUMB},1}} alt="{!IMAGE_ATTACHMENT,{$ATTACHMENT_DOWNLOADS*,{ID},{FORUM_DB_BIN}},{CLEAN_SIZE*}}"{+END}{+START,IF,{$NEQ,{A_THUMB},1}} title="{A_DESCRIPTION*}" alt="{A_DESCRIPTION*}"{+END}{+END}{+START,IF_PASSED_AND_TRUE,WYSIWYG_SAFE}{+START,IF,{$EQ,{A_THUMB},1}}{+END}{+START,IF,{$NEQ,{A_THUMB},1}} title="{A_DESCRIPTION*}"{+END} alt="{A_DESCRIPTION*}"{+END} />{+START,IF,{$EQ,{A_THUMB},1}}</a>{+END}
