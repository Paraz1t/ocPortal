{$,The line below is just a fudge - we need CHILDREN to evaluate first, so it doesn't interfere with our 'RAND' variable}
{$SET,HAS_CHILDREN,{$IS_NON_EMPTY,{CHILDREN}}}

{$SET,RAND,{$RAND}}

{$SET,img,{$?,{$AND,{$IS_EMPTY,{IMG}},{$LT,{THE_LEVEL},3}},{$IMG,icons/24x24/menu/_generic_spare/page},{IMG}}}

{+START,IF,{TOP_LEVEL}}
	<li{+START,IF,{$NOT,{$MOBILE}}}{+START,IF,{$OR,{$GET,HAS_CHILDREN},{$NOT,{LAST}}}} style="width: {$DIV_FLOAT',100,{BRETHREN_COUNT}}%"{+END}{+END} class="{$?,{CURRENT},current,non_current}{+START,IF,{$AND,{$NOT,{$GET,HAS_CHILDREN}},{LAST}}} last{+END} {+START,IF,{FIRST}} first{+END} toplevel"{+START,IF,{$GET,HAS_CHILDREN}}{+START,IF,{$NOT,{$MOBILE}}} onmousemove="if (!this.timer) this.timer=window.setTimeout(function() { return pop_up_menu('{MENU|;*}_dexpand_{$GET;*,RAND}','below','{MENU|;*}_d');} , 200);" onmouseout="if (this.timer) { window.clearTimeout(this.timer); this.timer=null; }"{+END}{+END}>
		<a{+START,INCLUDE,MENU_LINK_PROPERTIES}{+END} onkeypress="this.onclick(event);" onclick="cancel_bubbling(event); {+START,IF,{$GET,HAS_CHILDREN}}deset_active_menu(); {+END}{+START,IF_EMPTY,{URL}}return false;{+END}" class="{+START,IF_EMPTY,{URL}}non_link {+END}toplevel_link{+START,IF,{LAST}} last{+END}{+START,IF,{FIRST}} first{+END}"{+START,IF,{$GET,HAS_CHILDREN}} onfocus="return pop_up_menu('{MENU|;*}_dexpand_{$GET;*,RAND}','below','{MENU|;*}_d');"{+END}><img alt="" src="{$GET*,img}" /> <span>{CAPTION}</span></a>
		{+START,IF,{$GET,HAS_CHILDREN}}{+START,IF,{$NOT,{$MOBILE}}}
			<ul aria-haspopup="true" onmouseover="if (active_menu==null) return set_active_menu(this.id,'{MENU|;*}_d'); else return false;" onmouseout="return deset_active_menu();" class="nlevel" id="{MENU|*}_dexpand_{$GET;*,RAND}" style="display: none">{CHILDREN}</ul>
		{+END}{+END}
	</li>
{+END}

{+START,IF,{$NOT,{TOP_LEVEL}}}
	<li{+START,IF,{$GET,HAS_CHILDREN}} onmousemove="return pop_up_menu('{MENU|;*}_dexpand_{$GET;*,RAND}',null,'{MENU|;*}_d',event);"{+END} class="nlevel {$?,{CURRENT},current,non_current} has_img" onkeypress="this.onclick(event);" onclick="click_link(this.getElementsByTagName('a')[0]);">
		<img alt="" src="{$GET*,img}" />
		{+START,IF_NON_EMPTY,{URL}}
			<a{+START,INCLUDE,MENU_LINK_PROPERTIES}{+END} onkeypress="this.onclick(event);" onclick="cancel_bubbling(event);" {+START,IF,{$GET,HAS_CHILDREN}} class="drawer"{+END}>{CAPTION}</a>{+START,IF,{$GET,HAS_CHILDREN}}&nbsp;<span>&rarr;</span>{+END}
		{+END}
		{+START,IF_EMPTY,{URL}}
			<a onkeypress="this.onclick(event);" onclick="cancel_bubbling(event); return false;" class="non_link{+START,IF,{$GET,HAS_CHILDREN}} drawer{+END}" href="#">{CAPTION}</a>{+START,IF,{$GET,HAS_CHILDREN}}&nbsp;<span>&rarr;</span>{+END}
		{+END}
		{+START,IF,{$GET,HAS_CHILDREN}}
			<ul aria-haspopup="true" onmouseover="if (active_menu==null) return set_active_menu(this.id,'{MENU|;*}_d'); else return false;" onmouseout="return deset_active_menu();" class="nlevel" id="{MENU|*}_dexpand_{$GET;*,RAND}" style="display: none">{CHILDREN}</ul>
		{+END}
	</li>
{+END}
