<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\User as UserEntity;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:download-users',
    description: 'Download all users from https://reqres.in/api/users?page={page}',
)]
class DownloadUsersCommand extends Command
{
    private $page;

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('page', InputArgument::OPTIONAL, 'Get precise page (default 2)')        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->page = ($input->getArgument('page') == NULL) ? 2 : $input->getArgument('page');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://reqres.in/api/users?page={$this->page}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $json = json_decode($response);

        if(!empty($json->data)) {
            $i = 0;
            $em = $this->entityManager;
            $current_users = $em->getRepository(UserEntity::class)->findAll();
            $id_list = [];

            foreach($current_users as $user) {
                $id_list[] = $user->getId();
            }

            foreach($json->data as $user) {
                if(!empty($current_users) && in_array((int)$user->id, $id_list))
                    continue;
    
                $new_user = new UserEntity();
                $new_user->setId((int)$user->id);
                $new_user->setEmail($user->email);
                $new_user->setFirstName($user->first_name);
                $new_user->setLastName($user->last_name);
                $new_user->setAvatar($user->avatar);
    
                try {
                    $em->persist($new_user);
                    $em->flush();
                    $i++;
                } catch(Exception $e) {
                    // logs
                }
            }
        }
        
        $io = new SymfonyStyle($input, $output);

        if(empty($json->data)) {
            $io->note("This endpoint contains null data!");
        } elseif($i == 0) 
            $io->note("All users with these id numbers are existing!");
        else 
            $io->success("Added {$i} users");     

        return Command::SUCCESS;
    }
}
