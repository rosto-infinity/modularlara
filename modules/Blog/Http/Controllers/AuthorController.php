<?php

namespace Modules\Blog\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Response;
use Modules\Blog\Http\Requests\AuthorValidate;
use Modules\Blog\Models\Author;
use Modules\Support\Http\Controllers\BackendController;
use Modules\Support\Traits\UploadFile;

class AuthorController extends BackendController
{
    use UploadFile;

    /**
     * Affiche une liste paginée des auteurs avec options de recherche.
     *
     * @return Response
     */
    public function index(): Response
    {
        $authors = Author::orderBy('name') // Trie les auteurs par nom
            ->search(request('searchContext'), request('searchTerm')) // Applique la recherche
            ->paginate(request('rowsPerPage', 10)) // Paginer les résultats
            ->withQueryString() // Conserve les paramètres de requête
            ->through(fn ($author) => [ // Transforme les données des auteurs
                'id' => $author->id,
                'image_url' => $author->image_url,
                'name' => Str::limit($author->name, 50), // Limite le nom à 50 caractères
                'email' => $author->email,
                'github_handle' => $author->github_handle,
                'twitter_handle' => $author->twitter_handle,
            ]);

        return inertia('BlogAuthor/AuthorIndex', [ // Retourne la vue des auteurs
            'authors' => $authors,
        ]);
    }

    /**
     * -Affiche le formulaire pour créer un nouvel auteur.
     *
     * @return Response
     */
    public function create(): Response
    {
        return inertia('BlogAuthor/AuthorForm'); // Retourne la vue du formulaire de création
    }

    /**
     * -Enregistre un nouvel auteur dans la base de données.
     *
     * @param AuthorValidate $request Les données validées pour l'auteur.
     * @return RedirectResponse
     */
    public function store(AuthorValidate $request): RedirectResponse
    {
        $authorData = $request->validated(); // Valide et récupère les données

        // Vérifie si un fichier d'image est présent
        if ($request->hasFile('image')) {
            // Télécharge l'image et fusionne les données
            $authorData = array_merge($authorData, $this->uploadFile('image', 'blog', 'originalUUID', 'public'));
        }

        Author::create($authorData); // Crée un nouvel auteur

        return redirect()->route('blogAuthor.index') // Redirige vers la liste des auteurs
            ->with('success', 'Author created.'); // Message de succès
    }

    /**
     * -Affiche le formulaire pour modifier un auteur existant.
     *
     * @param int $id L'ID de l'auteur à modifier.
     * @return Response
     */
    public function edit(int $id): Response
    {
        $author = Author::find($id); // Récupère l'auteur par ID

        return inertia('BlogAuthor/AuthorForm', [ // Retourne la vue du formulaire de modification
            'author' => $author,
        ]);
    }

    /**
     * -Met à jour les informations d'un auteur existant.
     *
     * @param AuthorValidate $request Les données validées.
     * @param int $id L'ID de l'auteur à mettre à jour.
     * @return RedirectResponse
     */
    public function update(AuthorValidate $request, int $id): RedirectResponse
    {
        $author = Author::findOrFail($id); // Récupère l'auteur ou échoue

        $authorData = $request->validated(); // Valide les données

        // Vérifie si un fichier d'image est présent
        if ($request->hasFile('image')) {
            // Télécharge l'image et fusionne les données
            $authorData = array_merge($authorData, $this->uploadFile('image', 'blog', 'originalUUID', 'public'));
        } elseif ($request->input('remove_previous_image')) {
            // Supprime l'image précédente si demandé
            $authorData['image'] = null;
        } else {
            // -Supprime la clé image si aucune image n'est fournie
            unset($authorData['image']);
        }

        $author->update($authorData); // Met à jour l'auteur

        return redirect()->route('blogAuthor.index') // Redirige vers la liste des auteurs
            ->with('success', 'Author updated.'); // Message de succès
    }

    /**
     * -Supprime un auteur de la base de données.
     *
     * @param int $id L'ID de l'auteur à supprimer.
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        Author::findOrFail($id)->delete(); // Supprime l'auteur ou échoue

        return redirect()->route('blogAuthor.index') // Redirige vers la liste des auteurs
            ->with('success', 'Author deleted.'); // Message de succès
    }
}