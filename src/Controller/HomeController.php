<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use App\Repository\CategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(ProduitRepository $produitRepository, CategorieRepository $categorieRepository): Response
    {
        $stats = [
            'total_produits' => $produitRepository->countTotal(),
            'total_categories' => $categorieRepository->countTotal(),
            'produits_valables' => $produitRepository->countAvailable(),
            'featured_products' => $produitRepository->findAll(),
        ];

        return $this->render('front_home.html.twig', $stats);
    }

    #[Route('/produits', name: 'front_produits')]
    public function produits(Request $request, ProduitRepository $produitRepository, CategorieRepository $categorieRepository): Response
    {
        $search = $request->query->get('search', '');
        $categorie = $request->query->get('categorie', '');
        $sortBy = $request->query->get('sortBy', 'createdAt');
        $sortOrder = $request->query->get('sortOrder', 'DESC');

        $produits = $produitRepository->findByFilters($search, $categorie, $sortBy, $sortOrder);
        $categories = $categorieRepository->findAll();

        return $this->render('front_produits.html.twig', [
            'produits' => $produits,
            'categories' => $categories,
            'search' => $search,
            'categorie' => $categorie,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    #[Route('/produit/{id<\d+>}', name: 'front_detail')]
    public function detail(int $id, ProduitRepository $produitRepository): Response
    {
        $produit = $produitRepository->find($id);

        if (!$produit) {
            throw $this->createNotFoundException('Produit non trouvé');
        }

        // Produits connexes de la même catégorie
        $related_products = [];
        if ($produit->getCategorie()) {
            $all_in_category = $produitRepository->findByFilters('', $produit->getCategorie()->getId());
            // Exclure le produit courant
            $related_products = array_filter($all_in_category, function($p) use ($id) {
                return $p->getId() !== $id;
            });
        }

        return $this->render('front_detail.html.twig', [
            'produit' => $produit,
            'related_products' => $related_products,
        ]);
    }

    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(ProduitRepository $produitRepository, CategorieRepository $categorieRepository): Response
    {
        $statusByMonth = $produitRepository->getStatusByMonth();
        $mostExpensive = $produitRepository->getMostExpensiveProducts(5);
        $leastExpensive = $produitRepository->getLeastExpensiveProducts(5);

        $stats = [
            'total_produits' => $produitRepository->countTotal(),
            'total_categories' => $categorieRepository->countTotal(),
            'produits_expires' => $produitRepository->countExpired(),
            'produits_valables' => $produitRepository->countAvailable(),
            'produits_hors_stock' => $produitRepository->countOutOfStock(),
            'status_by_month' => $statusByMonth,
            'most_expensive' => $mostExpensive,
            'least_expensive' => $leastExpensive,
        ];

        return $this->render('sneat_dashboard.html.twig', $stats);
    }
}
