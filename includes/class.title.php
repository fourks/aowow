<?php

if (!defined('AOWOW_REVISION'))
    die('illegal access');

class TitleList extends BaseType
{
    use listviewHelper;

    public    $sources    = [];

    protected $setupQuery = 'SELECT *, id AS ARRAY_KEY FROM ?_titles WHERE [cond] ORDER BY Id ASC';
    protected $matchQuery = 'SELECT COUNT(1) FROM ?_titles WHERE [cond]';

    public function __construct($data)
    {
        parent::__construct($data);

        // post processing
        while ($this->iterate())
        {
            // overwrite names with gender-speciffics
            $this->names[$this->id][GENDER_MALE] = Util::localizedString($this->curTpl, 'male');
            if ($this->curTpl['female_loc0'] || $this->curTpl['female_loc'.User::$localeId])
                $this->names[$this->id][GENDER_FEMALE] = Util::localizedString($this->curTpl, 'female');

            // preparse sources
            if (!empty($this->curTpl['source']))
            {
                $sources = explode(' ', $this->curTpl['source']);
                foreach ($sources as $src)
                {
                    $src = explode(':', $src);
                    $this->sources[$this->id][$src[0]][] = $src[1];
                }
            }
        }
        $this->reset();                                     // push first element back for instant use
    }

    public function getListviewData()
    {
        $data = [];
        $this->createSource();

        while ($this->iterate())
        {
            $data[$this->id] = array(
                'id'        => $this->id,
                'name'      => $this->names[$this->id][GENDER_MALE],
                'side'      => $this->curTpl['side'],
                'gender'    => $this->curTpl['gender'],
                'expansion' => $this->curTpl['expansion'],
                'category'  => $this->curTpl['category']
            );

            if (!empty($this->curTpl['source']))
                $data[$this->id]['source'] = $this->curTpl['source'];
        }

        if (isset($this->name[GENDER_FEMALE]))
            $data['namefemale'] = $this->name[GENDER_FEMALE];

        return $data;
    }

    public function addGlobalsToJscript(&$refs)
    {
        if (!isset($refs['gTitles']))
            $refs['gTitles'] = [];

        while ($this->iterate())
        {
            $refs['gTitles'][$this->id]['name'] = Util::jsEscape($this->names[$this->id][GENDER_MALE]);

            if (isset($this->names[$this->id][GENDER_FEMALE]))
                $refs['gTitles'][$this->id]['namefemale'] = Util::jsEscape($this->names[$this->id][GENDER_FEMALE]);
        }
    }

    private function createSource()
    {
        $sources = array(
            4  => [],                                       // Quest
            12 => [],                                       // Achievements
            13 => []                                        // simple text
        );

        while ($this->iterate())
        {
            if (empty($this->sources[$this->id]))
                continue;

            foreach (array_keys($sources) as $srcKey)
                if (isset($this->sources[$this->id][$srcKey]))
                    $sources[$srcKey] = array_merge($sources[$srcKey], $this->sources[$this->id][$srcKey]);
        }

        // fill in the details
        if (!empty($sources[4]))
            $sources[4] = (new QuestList(array(['id', $sources[4]])))->getSourceData();

        if (!empty($sources[12]))
            $sources[12] = (new AchievementList(array(['id', $sources[12]])))->getSourceData();

        if (!empty($sources[13]))
            $sources[13] = DB::Aowow()->SELECT('SELECT *, Id AS ARRAY_KEY FROM ?_sourceStrings WHERE Id IN (?a)', $sources[13]);

        foreach ($this->sources as $Id => $src)
        {
            $tmp = [];

            // Quest-source
            if (isset($src[4]))
                foreach ($src[4] as $s)
                    $tmp[4][] = $sources[4][$s];

            // Achievement-source
            if (isset($src[12]))
                foreach ($src[12] as $s)
                    $tmp[12][] = $sources[12][$s];

            // other source (only one item possible, so no iteration needed)
            if (isset($src[13]))
                $tmp[13] = [Util::localizedString($sources[13][$this->sources[$Id][13][0]], 'source')];

            $this->templates[$Id]['source'] = json_encode($tmp);
        }
    }

    public function getHtmlizedName($gender = GENDER_MALE)
    {
        return str_replace('%s', '<span class="q0">&lt;'.Lang::$main['name'].'&gt;</span>', $this->names[$this->id][$gender]);
    }

    public function addRewardsToJScript(&$ref) { }
    public function renderTooltip() { }
}

?>