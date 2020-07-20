<?php

namespace App\Controller\API\Connect;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\HttpClient;

use App\Entity\User;
use App\Entity\Connection;
use App\Entity\ConnectApp;

class APIConnectController extends AbstractController
{
    /**
     * @Route("/api/connect/generateCode", name="api.connect.generateCode")
     */
    public function generateCode()
    {
        $em = $this->getDoctrine()->getManager();
        $data = [];

        $user = $em->getRepository(User::class)->findOneBy(array('id' => $this->getUser()->getID()));

        if($user) {
            $newCode = substr(md5(time() * $user->getID()), 0, 6);
            $user->setConnectCode($newCode);

            $em->persist($user);
            $em->flush();

            $response = new JsonResponse(['version' => $this->getParameter('api_version'), 'status' => 200, 'data' => $newCode]);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            return $response;
        } else {
            $response = new JsonResponse(['version' => $this->getParameter('api_version'), 'status' => 404, 'data' => []]);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            return $response;
        }
    }
    /**
     * @Route("/api/connect/getToken", name="api.connect.getToken")
     */
    public function getToken(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $data = [];

        $connectCode = $request->query->get('connectCode');
        $connectAppApiKey = $request->query->get('connectAppApiKey');

        if($connectCode == "" || $connectAppApiKey == "") {
            $response = new JsonResponse(['version' => $this->getParameter('api_version'), 'status' => 400, 'data' => ["connectCode" => $connectCode, "connectAppApiKey" => $connectAppApiKey]]);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            return $response;
        }

        $user = $em->getRepository(User::class)->findOneBy(array('connectCode' => $connectCode));
        $connectApp = $em->getRepository(ConnectApp::class)->findOneBy(array('apiKey' => $connectAppApiKey));

        if($user && $connectApp) {
            $newConnectToken = md5(time() * $user->getID());

            // Create Connection
            $newConnection = new Connection();
            $newConnection->setUser($user);
            $newConnection->setApp($connectApp);
            $newConnection->setConnectDate(new \DateTime('now'));
            $newConnection->setConnectToken($newConnectToken);

            // Output Connection Token

            // Invalidate ConnectionCode
            $user->setConnectCode("");
            $em->persist($user);
            $em->persist($newConnection);
            $em->flush();

            $response = new JsonResponse(['version' => $this->getParameter('api_version'), 'status' => 200, 'data' => $newConnectToken]);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            return $response;
        } else {
            $response = new JsonResponse(['version' => $this->getParameter('api_version'), 'status' => 404, 'data' => []]);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            return $response;
        }
    }

    /**
     * @Route("/api/connect/validateToken/", name="api.connect.validateToken")
     */
    public function validateToken(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $data = [];

        $connectToken = $request->query->get('connectToken');

        $connection = $em->getRepository(Connection::class)->findOneBy(array('connectToken' => $connectToken));

        if($connectToken != "" && $connection) {
            $response = new JsonResponse(['version' => $this->getParameter('api_version'), 'status' => 200, 'data' => []]);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            return $response;
        } else {
            $response = new JsonResponse(['version' => $this->getParameter('api_version'), 'status' => 404, 'data' => []]);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            return $response;
        }
    }
}
