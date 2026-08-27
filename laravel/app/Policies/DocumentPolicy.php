<?php

namespace App\Policies;

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\Organization;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) !== null;
    }

    public function view(User $user, Document $document): bool
    {
        return $user->roleIn($document->organization) !== null;
    }

    /**
     * Chair, secretary — or the treasurer, but only for financial
     * documents (screen 21: "Unggah dokumen (chair, secretary, treasurer
     * for financial documents)").
     */
    public function create(User $user, Organization $organization, DocumentCategory $category): bool
    {
        if ($user->isSecretaryOf($organization)) {
            return true;
        }

        return $category === DocumentCategory::Laporan && $user->isTreasurerOf($organization);
    }

    /**
     * The uploader, or any pengurus (screen 22: "uploader / pengurus see
     * ghost 'Hapus dokumen'").
     */
    public function delete(User $user, Document $document): bool
    {
        if ($user->id === $document->uploaded_by) {
            return true;
        }

        return $user->isPengurusOf($document->organization);
    }
}
