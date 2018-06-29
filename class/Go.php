<?php

use Colors\Color;
use Spatie\Async\Pool;

class Go
{
    protected $repos;
    protected $config;
    protected $c;
    protected $params;

    public function __construct($params)
    {
        $this->params = $params;
        $this->c = new Color();
        $this->c->setTheme([
            'success' => ['white', 'bg_green'],
            'info' => ['white', 'bg_blue'],
            'error' => ['white', 'bg_red'],
            'question' => ['white', 'bg_red'],
            'bye' => ['white', 'bg_dark_gray'],
        ]);


        $json = file_exists($params['file_repo']) ? json_decode(file_get_contents($params['file_repo']))->repo : null;
        if($json === null) {
            echo $this->c->colorize('<error>File Repo -> <bold>'.$params['file_repo'].'</bold></error><error> corrupt or not found </error>').PHP_EOL;
            exit();
        }

        $this->config = file_exists($params['file_config']) ? json_decode(file_get_contents($params['file_config'])) : null;
        if($this->config === null) {
            echo $this->c->colorize('<error>File Configuration -> <bold>'.$params['file_config'].'</bold></error><error> corrupt or not found </error>').PHP_EOL;
            exit();
        }

        if($params['postCommands']) $this->config->postCommands = true;

        if($params['groups'] || $params['repos']) $this->config->skip_question = true;

        $this->check_command_exist($this->config->checkCommandInit);

        echo $this->c->colorize('<bye>
        
        ██╗    ██╗███████╗██╗      ██████╗ ██████╗ ███╗   ███╗███████╗
        ██║    ██║██╔════╝██║     ██╔════╝██╔═══██╗████╗ ████║██╔════╝
        ██║ █╗ ██║█████╗  ██║     ██║     ██║   ██║██╔████╔██║█████╗  
        ██║███╗██║██╔══╝  ██║     ██║     ██║   ██║██║╚██╔╝██║██╔══╝  
        ╚███╔███╔╝███████╗███████╗╚██████╗╚██████╔╝██║ ╚═╝ ██║███████╗
         ╚══╝╚══╝ ╚══════╝╚══════╝ ╚═════╝ ╚═════╝ ╚═╝     ╚═╝╚══════╝
                                                                      
                                            <bold>Script PHP -> Git Sync v.1</bold></bye><bye>              
                                                            <underline>By Rob.Cal</underline>
              
        </bye>').PHP_EOL;

        $this->repos = collect($json)->where('active',true);

        if($this->config->postCommands){
            $this->check_command_exist($this->repos->pluck('postCommand')->unique()->collapse());
        }
    }

    public function init()
    {
        $repos = null;
        if(!$this->params['groups'] && !$this->params['repos']){
            $repos = $this->selectGroup($this->repos);
            $select_repo = $this->selectRepo($repos);

            if(!$select_repo['all']){
                $repos = $repos->whereIn('title', $select_repo['extract']);
            }

        } else {

            if($this->params['groups']){
                $groups = explode(', ', $this->params['groups']);
                $repos = $this->repos->whereIn('group', $groups);
            } else if($this->params['repos']) {
                $repos = explode(', ', $this->params['repos']);
                $repos = $this->repos->whereIn('title', $repos);
            }
        }


        if($this->config->priority) {
            $repos = $repos->sortBy('priority');
        }

        if($this->config->async->active) {
            echo $this->c->colorize('<info>Active asynchronous mode, in this case several processes will be allocated in parallel</info>').PHP_EOL;
            return $this->async($repos);
        }

        foreach ($repos as $repo) {
            if(file_exists($repo->destination)) {
                echo $this->c->colorize('<info>Running Git Pull -> <bold>'.$repo->title.'</bold></info><info> on destination folder -> <bold>'.$repo->destination.'</bold></info>').PHP_EOL;
                exec("git -C '" . $repo->destination . "' " . $repo->gitAction );
                if($this->config->postCommands && isset($repo->postCommand)) exec($this->postCommands($repo));
            } else {
                $msg = "<info>The repository <bold>".$repo->title."</bold></info><info>does not exist locally I run the git clone on destination folder ? -> <bold>" . $repo->destination . "</bold></info>";
                if($this->config->skip_question || $this->promptYesNoAll($msg)){
                    echo $this->c->colorize("<info>eseguo il clone del progi -> <bold>".$repo->title."</bold></info><info> su -> <bold>". $repo->destination."</bold></info>").PHP_EOL;
                    exec("git clone " . $repo->uri . " '" . $repo->destination . "'");
                    if($this->config->postCommands && isset($repo->postCommand)) exec($this->postCommands($repo));
                } else {
                    echo $this->c->colorize("<bye>Step forward</bye>").PHP_EOL;
                }
            }

            $this->cacheGitCredential();
        }

        return true;
    }

    public function async($repos = null)
    {
        if($repos === null) {
            if(!$this->config->skip_question){
                $msg = "<info>Si intende usare <bold>tutti i repository?</bold></info>";
                if(!$this->promptYesNoAll($msg)) $this->quit();
            }

            echo $this->c->colorize('<success>Ok procedo a scaricare tutti i repository</success>').PHP_EOL;
            $repos = $this->repos;
        }

        $concurrency = $this->config->async->concurrency?:10;

        $pool = Pool::create()->concurrency($concurrency);

        foreach ($repos as $repo) {

            $postCommands = ($this->config->postCommands && isset($repo->postCommand)) ? $this->postCommands($repo) : null;

            $pool->add(function () use ($repo, $postCommands) {
                if(file_exists($repo->destination)) {
                    exec("git -C '" . $repo->destination . "' " . $repo->gitAction);
                } else {
                    exec("git clone " . $repo->uri . " '" . $repo->destination . "'");
                }

                if($postCommands) exec($postCommands);

                return $repo->title;

            })->then(function ($output) use($repo) {
                echo $this->c->colorize('<success>Ok -> <bold>'.$output.'</bold></success>').PHP_EOL;
            })->catch(function (Throwable $exception) use($repo) {
                echo $this->c->colorize('<success>'.$exception->getMessage().'</success>').PHP_EOL;
                return false;
            });
        }

        $pool->wait();

        echo $this->c->colorize('<info>Finish! ;)</info>').PHP_EOL;

        return true;
    }

    private function postCommands($repo)
    {
        $cmd = "cd $repo->destination ";
        foreach ($repo->postCommand as $command){
            $cmd .= "&& " . $command . " ";
        }

        return $cmd;
    }

    private function selectGroup($repos)
    {
        $groups = $repos->pluck('group')->unique();
        $all_keys = collect('*** A L L ***');
        $groups = $all_keys->merge($groups);
        $groups[999] = "*** Q U I T ***";

        do {
            $selected_group = $this->promptSelectGroup($groups);
        }
        while (!array_key_exists($selected_group, $groups->toArray()));

        if($selected_group == "999") $this->quit();

        if($selected_group !== "0") {
            echo $this->c->colorize('<info>Selected group -> <bold>'.$groups[$selected_group].'</bold></info>').PHP_EOL;
            return $repos->where('group', $groups[$selected_group]);
        } else {
            echo $this->c->colorize('<info>I run on <bold>all</bold> the repositories</info>').PHP_EOL;
            return $repos;
        }
    }

    private function promptYesNoAll($msg)
    {
        echo $this->c->colorize($msg).PHP_EOL;
        echo $this->c->colorize('<question>1 => yes | 2 => no | 3 => do not ask anymore</question>').PHP_EOL;
        $handle = fopen ("php://stdin","r");
        $line = trim(fgets($handle));
        if($line === '1'){
            fclose($handle);
            return true;
        } elseif ($line === '2') {
            fclose($handle);
            return false;
        } elseif ($line === '3') {
            fclose($handle);
            $this->config->skip_question = true;
            return true;
        } else {
            fclose($handle);
            echo $this->c->colorize('<info>1 | 2 | 3</info>').PHP_EOL;
            $this->promptYesNoAll($msg);
        }
    }

    private function promptSelectGroup($groups)
    {
        echo $this->c->colorize('<question>Which group do you want to use?</question>').PHP_EOL;
        dump($groups->toArray());

        echo $this->c->colorize('<info>Enter the number of the array</info>').PHP_EOL;
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);

        fclose($handle);
        return trim($line);
    }

    private function selectRepo($repos)
    {
        $all_keys = collect('*** A L L ***');
        $listRepo = $all_keys->merge($repos->pluck('title')->toArray());
        $listRepo[999] = "*** Come Back ***";

        do {
            $validation = false;
            $selected_repo = $this->promptSelectRepo($listRepo);
            $extract = [];
            $all = false;

            if($selected_repo != "0"){
                foreach (explode( ',', $selected_repo) as $k) {
                    if(array_key_exists($k, $listRepo->toArray())) {
                        $extract[] = $listRepo->toArray()[$k];
                    } else {
                        $validation = true;
                    }
                }
            } else {
                $validation = false;
                $all = true;
            }

        }
        while ($validation);

        return ['all' => $all, 'extract' => $extract];
    }

    private function promptSelectRepo($repo)
    {
        echo $this->c->colorize('<question>Which of these repositories do you want to use?</question>').PHP_EOL;
        dump($repo->toArray());

        echo $this->c->colorize('<info>Enter one or more numbers in the array, you can use the following separators [space][.][+][,] example (1.2.3) or (1,2,3) or (1+2+3) or (1 2 3)</info>').PHP_EOL;
        $handle = fopen ("php://stdin","r");
        $line = trim(fgets($handle));

        if($line === "999") return $this->init();

        fclose($handle);
        return preg_replace('/\+|[\.]|[[:space:]]/', ',', $line);
    }

    private function cacheGitCredential()
    {
        if($this->config->git->cacheCredential){
            $timeout = $this->config->git->cacheCredentialTimeout?:10000;
            exec("git config --global credential.helper 'cache --timeout=".$timeout."'");
        }
    }

    private function check_command_exist($commands)
    {
        if(count($commands)){
            $output = [];
            foreach ($commands as $cmd){
                $cmd = explode(' ', $cmd)[0];
                if(!shell_exec(sprintf("which %s", escapeshellarg($cmd)))) {
                    $output[] = $cmd;
                }
            }
            if(count($output)){
                echo $this->c->colorize('<error>Error! commands not found -> <bold>'.implode(', ', $output).'</bold></error>').PHP_EOL;
                exit();
            }
        }

    }

    private function quit()
    {
        echo $this->c->colorize('<bye>Goodbye ;)</bye>').PHP_EOL;
        exit();
    }
}