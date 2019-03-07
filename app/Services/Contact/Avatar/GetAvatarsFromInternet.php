<?php

namespace App\Services\Contact\Avatar;

use App\Helpers\RandomHelper;
use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GetAvatarsFromInternet extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'contact_id' => 'required|integer|exists:contacts,id',
        ];
    }

    /**
     * Query both Gravatar and Adorable Avatars based on the email address of
     * the contact.
     *
     * - http://avatars.adorable.io/ gives avatars based on a random string.
     * This random string comes from the `avatar_adorable_uuid` field in the
     * Contact object.
     * - Gravatar only gives an avatar only if it's set.
     *
     * @param array $data
     * @return Contact
     */
    public function execute(array $data): Contact
    {
        $this->validate($data);

        $contact = Contact::findOrFail($data['contact_id']);

        $contact = $this->generateUUID($contact);
        $this->getAdorable($contact);
        $this->getGravatar($contact);

        return $contact;
    }

    /**
     * Generate the UUID used to identify the contact in the Adorable service.
     *
     * @param Contact  $contact
     * @return Contact
     */
    private function generateUUID(Contact $contact)
    {
        $contact->avatar_adorable_uuid = RandomHelper::uuid();
        $contact->save();

        return $contact;
    }

    /**
     * Get the adorable avatar.
     *
     * @param Contact  $contact
     * @return void
     */
    private function getAdorable(Contact $contact)
    {
        $contact->avatar_adorable_url = app(GetAdorableAvatarURL::class)->execute([
            'uuid' => $contact->avatar_adorable_uuid,
            'size' => 200,
        ]);
        $contact->save();
    }

    /**
     * Get the email (if it exists) of the contact, based on the contact fields.
     *
     * @param Contact $contact
     * @return null|string
     */
    private function getEmail(Contact $contact)
    {
        try {
            $contactField = $contact->contactFields()
                ->whereHas('contactFieldType', function ($query) {
                    $query->where('type', '=', 'email');
                })
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return;
        }

        return $contactField->data;
    }

    /**
     * Query Gravatar (if it exists) for the contact's email address.
     *
     * @param Contact  $contact
     * @return void
     */
    private function getGravatar(Contact $contact)
    {
        $email = $this->getEmail($contact);

        if ($email) {
            $contact->avatar_gravatar_url = app(GetGravatarURL::class)->execute([
                'email' => $email,
                'size' => 200,
            ]);
        } else {
            // in this case we need to make sure that we reset the gravatar URL
            $contact->avatar_gravatar_url = null;

            if ($contact->avatar_source == 'gravatar') {
                $contact->avatar_source = 'adorable';
            }
        }

        $contact->save();
    }
}
