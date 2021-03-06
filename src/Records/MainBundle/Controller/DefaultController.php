<?php

namespace Records\MainBundle\Controller;

use Records\MainBundle\Entity\Archive;
use Records\MainBundle\Entity\Record;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('MainBundle:Default:index.html.twig');
    }

    /**
     * Every 22 minutes a student takes a type of test and submits their score.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction()
    {
        $em = $this->getDoctrine()->getManager();
        $records = array();
        for($i = 0; $i < 22; $i++){
            $em = $this->createRandomRecord($em);
        }
        $em->flush();

        return $this->render('MainBundle:Default:create.html.twig', array(
            'records' => array()
        ));
    }

    /**
     * Every minute the score keeper records the total amount of students who took a type of test
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function moveAction()
    {
        $em = $this->getDoctrine()->getManager();
        $archives = array();
        $records = $em->getRepository('MainBundle:Record')->findBy(
            array( 'archived' => false ),
            array( 'dateCreated' => 'desc'),
            22 ,0
        );
        foreach( $records as $key => $value ) {
            $dateCreated = $value->getDateCreated()->format('Y-m-d H:i:s');
            $type = $value->getType();
            $score = $value->getScore();
            if (!isset($archives[$dateCreated])) $archives[$dateCreated] = array();
            if (!isset($archives[$dateCreated][$type])) $archives[$dateCreated][$type] = 0;

            $archives[$dateCreated][$type] ++;
        }

        foreach( $archives as $dateCreated => $value) {
            foreach ($value as $type => $amountOfStudents ) {
                $em = $this->createArchive($em, $dateCreated, $type, $amountOfStudents);
            }
        }
        $em->flush();

        return $this->render('MainBundle:Default:moveRecord.html.twig', array(
            'records' => $records,
            'archives' => $archives,
        ));
    }

    /**
     * Create and move data in one call
     * @return \Symfony\Component\HttpFoundation\Response
     */
    function createAndMoveAction()
    {
        $em = $this->getDoctrine()->getManager();
        $records = array();
        for($i = 0; $i < 22; $i++){
            $em = $this->createRandomRecord($em);
        }
        $em->flush();

        sleep(1);

        $archives = array();
        $records2 = $em->getRepository('MainBundle:Record')->findBy(
            array( 'archived' => false ),
            array( 'dateCreated' => 'desc'),
            22 ,0
        );
        foreach( $records2 as $key => $value ) {
            $dateCreated = $value->getDateCreated()->format('Y-m-d H:i:s');
            $type = $value->getType();
            $score = $value->getScore();
            if (!isset($archives[$dateCreated])) $archives[$dateCreated] = array();
            if (!isset($archives[$dateCreated][$type])) $archives[$dateCreated][$type] = 0;
            $archives[$dateCreated][$type] ++;
        }

        foreach( $archives as $dateCreated => $value) {
            foreach ($value as $type => $amountOfStudents ) {
                $em = $this->createArchive($em, $dateCreated, $type, $amountOfStudents);
            }
        }
        $em->flush();

        return $this->render('MainBundle:Default:createAndMoveRecord.html.twig', array(
            'records' => $records2,
            'records2' => $records2,
            'archives' => $archives,
        ));
    }

    private function createRandomRecord($em){
        $record = new Record();
        $record->setDateCreated(new \DateTime('now'));
        $record->setName(uniqid());
        $record->setScore(rand(1,100));
        $record->setType(rand(1,10));
        $record->setArchived(false);
        $records[] = $record;
        $em->persist($record);
        return $em;
    }

    private function createArchive($em, $dateCreated, $type, $amountOfStudents)
    {
        $archive = new Archive();
        $archive->setDateCreated(new \DateTime($dateCreated));
        $archive->setType('Total_' . $type);
        $archive->setScore($amountOfStudents);
        $em->persist($archive);
        return $em;
    }
}
