{+START,IF_ADJACENT,OCF_USER_MEMBER}, {+END}{+START,IF_PASSED,HIGHLIGHT_NAME}{+START,IF,{HIGHLIGHT_NAME}}<em>{+END}{+END}{+START,IF_PASSED,AT}<a {+START,IF_PASSED,COLOUR}class="{COLOUR*}" {+END}title="{USERNAME*}: {!LAST_VIEWED}&hellip; {AT#}" href="{PROFILE_URL*}">{USERNAME*}</a>{+END}{+START,IF_NON_PASSED,AT}<a {+START,IF_PASSED,COLOUR}class="{COLOUR*}" {+END}title="{USERNAME*}: {+START,IF_PASSED,USERGROUP}{USERGROUP*}{+END}{+START,IF_NON_PASSED,USERGROUP}{!MEMBER}{+END}" href="{PROFILE_URL*}">{USERNAME*}</a>{+END}{+START,IF_PASSED,HIGHLIGHT_NAME}{+START,IF,{HIGHLIGHT_NAME}}</em>{+END}{+END}{+START,IF_PASSED,AGE} ({AGE*}){+END}
