<?php

if (!defined('AOWOW_REVISION'))
    die('illegal access');


/*
    icons: data/interface/calendar/calendar_[a-z]start.blp
*/


// menuId 10: Title    g_initPath()
//  tabId  0: Database g_initHeader()
class TitlePage extends GenericPage
{
    use DetailPage;

    protected $type          = TYPE_TITLE;
    protected $typeId        = 0;
    protected $tpl           = 'detail-page-generic';
    protected $path          = [0, 10];
    protected $tabId         = 0;
    protected $mode          = CACHE_TYPE_PAGE;

    private   $nameFixed     = '';

    public function __construct($pageCall, $id)
    {
        parent::__construct($pageCall, $id);

        $this->typeId = intVal($id);

        $this->subject = new TitleList(array(['id', $this->typeId]));
        if ($this->subject->error)
            $this->notFound(Lang::game('title'));

        $this->name      = $this->subject->getHtmlizedName();
        $this->nameFixed = Util::ucFirst(trim(strtr($this->subject->getField('male', true), ['%s' => '', ',' => ''])));
    }

    protected function generatePath()
    {
        $this->path[] = $this->subject->getField('category');
    }

    protected function generateTitle()
    {
        array_unshift($this->title, $this->nameFixed, Util::ucFirst(Lang::game('title')));
    }

    protected function generateContent()
    {
        /***********/
        /* Infobox */
        /***********/

        $infobox = Lang::getInfoBoxForFlags($this->subject->getField('cuFlags'));

        if ($this->subject->getField('side') == SIDE_ALLIANCE)
            $infobox[] = Lang::main('side').Lang::main('colon').'[span class=icon-alliance]'.Lang::game('si', SIDE_ALLIANCE).'[/span]';
        else if ($this->subject->getField('side') == SIDE_HORDE)
            $infobox[] = Lang::main('side').Lang::main('colon').'[span class=icon-horde]'.Lang::game('si', SIDE_HORDE).'[/span]';
        else
            $infobox[] = Lang::main('side').Lang::main('colon').Lang::game('si', SIDE_BOTH);

        if ($g = $this->subject->getField('gender'))
            $infobox[] = Lang::main('gender').Lang::main('colon').'[span class=icon-'.($g == 2 ? 'female' : 'male').']'.Lang::main('sex', $g).'[/span]';

        if ($e = $this->subject->getField('eventId'))
            $infobox[] = Lang::game('eventShort').Lang::main('colon').'[url=?event='.$e.']'.WorldEventList::getName($e).'[/url]';

        /****************/
        /* Main Content */
        /****************/

        $this->infobox    = $infobox ? '[ul][li]'.implode('[/li][li]', $infobox).'[/li][/ul]' : null;
        $this->expansion  = Util::$expansionString[$this->subject->getField('expansion')];
        $this->redButtons = array(
            BUTTON_WOWHEAD => true,
            BUTTON_LINKS   => ['name' => $this->nameFixed]
        );

        // factionchange-equivalent
        if ($pendant = DB::World()->selectCell('SELECT IF(horde_id = ?d, alliance_id, -horde_id) FROM player_factionchange_titles WHERE alliance_id = ?d OR horde_id = ?d', $this->typeId, $this->typeId, $this->typeId))
        {
            $altTitle = new TitleList(array(['id', abs($pendant)]));
            if (!$altTitle->error)
            {
                $this->transfer = sprintf(
                    Lang::title('_transfer'),
                    $altTitle->id,
                    $altTitle->getHtmlizedName(),
                    $pendant > 0 ? 'alliance' : 'horde',
                    $pendant > 0 ? Lang::game('si', 1) : Lang::game('si', 2)
                );
            }
        }

        /**************/
        /* Extra Tabs */
        /**************/

        // tab: quest source
        $quests = new QuestList(array(['rewardTitleId', $this->typeId]));
        if (!$quests->error)
        {
            $this->extendGlobalData($quests->getJSGlobals(GLOBALINFO_REWARDS));

            $this->lvTabs[] = array(
                'file'   => 'quest',
                'data'   => $quests->getListviewData(),
                'params' => array(
                    'id'          => 'reward-from-quest',
                    'name'        => '$LANG.tab_rewardfrom',
                    'hiddenCols'  => "$['experience', 'money']",
                    'visibleCols' => "$['category']"
                )
            );
        }

        // tab: achievement source
        $acvs = new AchievementList(array(['ar.title_A', $this->typeId], ['ar.title_H', $this->typeId], 'OR'));
        if (!$acvs->error)
        {
            $this->extendGlobalData($acvs->getJSGlobals());

            $this->lvTabs[] = array(
                'file'   => 'achievement',
                'data'   => $acvs->getListviewData(),
                'params' => array(
                    'id'          => 'reward-from-achievement',
                    'name'        => '$LANG.tab_rewardfrom',
                    'visibleCols' => "$['category']",
                    'sort'        => "$['reqlevel', 'name']"
                )
            );
        }

        // tab: criteria of (to be added by TC)
    }
}

?>
