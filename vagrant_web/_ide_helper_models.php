<?php

// @formatter:off
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * Class Material
 *
 * @package App\Models
 * @property int        $id
 * @property string     $title
 * @property string     $description
 * @property int        $rating (0-20)
 * @property boolean    $from_bot
 * @property int        $created_by
 * @property int        $modified_by
 * @property int        $author_id
 * @property Person     $author
 * @property Collection $resources;
 * @property Collection $keywords;
 * @property Collection $foreignIds;
 * @property Collection $persons;
 * @property Collection $languages;
 * @property Collection $tags;
 * @property Collection $places;
 * @property User|NULL  $creator;
 * @property User|NULL  $modifier;
 * @property Collection $bibleverses;
 * @property $updated_at;
 * @property $created_at;
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Bibleverse[] $bibleverses
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ForeignMaterialId[] $foreignIds
 * @property-read \App\Models\User $modifier
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Resource[] $resources
 */
	class Material extends \Eloquent {}
}

namespace App\Models{
/**
 * Class Keyword
 *
 * @property int        $id
 * @property string     $title
 * @property string     $type
 * @property string     $lc_title
 * @property int        $parent_id
 * @property string     $custom_icon
 * @property Collection $materials
 * @property-read \Kalnoy\Nestedset\Collection|\App\Models\Keyword[] $children
 * @property mixed $icon
 * @property-read \App\Models\Keyword $parent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Keyword d()
 */
	class Keyword extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Book
 *
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ForeignResourceId[] $foreignIds
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Material[] $materials
 */
	class Book extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Place
 *
 * @property-read \Kalnoy\Nestedset\Collection|\App\Models\Place[] $children
 * @property mixed $icon
 * @property-read mixed $type
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Material[] $materials
 * @property-read \App\Models\Place $parent
 * @property-write mixed $custom_icon
 * @property-write mixed $parent_id
 * @property-write mixed $title
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Keyword d()
 */
	class Place extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\PdfFile
 *
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ForeignResourceId[] $foreignIds
 * @property mixed $original_filename
 * @property int|NULL $page_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Material[] $materials
 * @property-write mixed $local_path
 * @property-write mixed $remote_path
 */
	class PdfFile extends \Eloquent {}
}

namespace App\Models{
/**
 * Class ForeignResourceId
 *
 * @package App\Models
 * @property int      $id
 * @property int      $resource_id
 * @property int      $foreign_id
 * @property $created_at
 * @property $updated_at
 * @property Resource $resource
 * @property User     $user
 * @property Bundle   $bundle
 * @property int      $user_id
 */
	class ForeignResourceId extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\ImageFile
 *
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ForeignResourceId[] $foreignIds
 * @property mixed $original_filename
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Material[] $materials
 * @property-write mixed $local_path
 * @property-write mixed $remote_path
 */
	class ImageFile extends \Eloquent {}
}

namespace App\Models{
/**
 * Class File
 *
 * @package App\Models
 * @property string|null $original_filename
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ForeignResourceId[] $foreignIds
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Material[] $materials
 * @property-write mixed $local_path
 * @property-write mixed $remote_path
 */
	class File extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\AudioFile
 *
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ForeignResourceId[] $foreignIds
 * @property mixed $original_filename
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Material[] $materials
 * @property-write mixed $local_path
 * @property-write mixed $remote_path
 */
	class AudioFile extends \Eloquent {}
}

namespace App\Models{
/**
 * Class User
 *
 * @package App\Models
 * @property string     $name
 * @property string     $email
 * @property string     $password
 * @property string     $remember_token
 * @property int        $id
 * @property boolean    $is_admin
 * @property Collection $foreignResourceIds
 * @property Collection $foreignMaterialIds
 * @property Collection $resources
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Passport\Client[] $clients
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Passport\Token[] $tokens
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * Class MaterialResource
 *
 * @property int $relevance
 */
	class MaterialKeyword extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Person
 *
 * @property-read \Kalnoy\Nestedset\Collection|\App\Models\Person[] $children
 * @property mixed $icon
 * @property-read mixed $type
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Material[] $materials
 * @property-read \App\Models\Person $parent
 * @property-write mixed $custom_icon
 * @property-write mixed $parent_id
 * @property-write mixed $title
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Keyword d()
 */
	class Person extends \Eloquent {}
}

namespace App\Models{
/**
 * Class Bundle
 *
 * @package App\Models
 * @property int       $id
 * @property string    $author
 * @property string    installed_version
 * @property \DateTime last_update
 * @property string    $uuid
 * @property string    $container_root
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property string    $name
 * @property string    $description
 * @property bool      $is_installed
 * @property bool      $update_available
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ForeignMaterialId[] $foreignMaterialds
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ForeignResourceId[] $foreignResourceIds
 */
	class Bundle extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Url
 *
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ForeignResourceId[] $foreignIds
 * @property-read mixed $content
 * @property mixed $url
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Material[] $materials
 */
	class Url extends \Eloquent {}
}

namespace App\Models{
/**
 * Class MaterialResource
 *
 * @property object $limitation
 */
	class MaterialResource extends \Eloquent {}
}

namespace App\Models{
/**
 * Class MaterialResource
 *
 * @property int $relevance
 */
	class MaterialBibleverse extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Language
 *
 * @property-read \Kalnoy\Nestedset\Collection|\App\Models\Language[] $children
 * @property mixed $icon
 * @property-read mixed $type
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Material[] $materials
 * @property-read \App\Models\Language $parent
 * @property-write mixed $custom_icon
 * @property-write mixed $parent_id
 * @property-write mixed $title
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Keyword d()
 */
	class Language extends \Eloquent {}
}

namespace App\Models{
/**
 * Class Bibleverse
 *
 * @package App\Modules
 * @property int      $from
 * @property int      $to
 * @property int|null $bible_id
 * @property string   $label
 * @property int      $from_book_id
 * @property int      $from_chapter
 * @property int      $from_verse
 * @property int      $to_book_id
 * @property int      $to_chapter
 * @property int      $to_verse
 * @property int      $icon
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Material[] $materials
 * @property-write mixed $book_id
 */
	class Bibleverse extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\VideoFile
 *
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ForeignResourceId[] $foreignIds
 * @property mixed $original_filename
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Material[] $materials
 * @property-write mixed $local_path
 * @property-write mixed $remote_path
 */
	class VideoFile extends \Eloquent {}
}

namespace App\Models{
/**
 * Class Resource
 *
 * @package App
 * @property int        $id
 * @property int        $created_by
 * @property string     $remote_path
 * @property string     $local_path
 * @property string     $content_hash
 * @property string     $notes
 * @property bool       $is_public
 * @property $created_at
 * @property $updated_at
 * @property int        $user_id
 * @property User       $creator
 * @property Collection $materials
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ForeignResourceId[] $foreignIds
 */
	class Resource extends \Eloquent {}
}

namespace App\Models{
/**
 * Class ForeignMaterialId
 *
 * @package App\Models
 * @property int      $id
 * @property int      $material_id
 * @property int      $foreign_id
 * @property int      $user_id
 * @property $created_at
 * @property $updated_at
 * @property Material $material
 * @property User     $user
 * @property Bundle   $bundle
 */
	class ForeignMaterialId extends \Eloquent {}
}

namespace App\Models{
/**
 * Class Text
 *
 * @package App\Models
 * @property string $content
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ForeignResourceId[] $foreignIds
 * @property mixed $original_filename
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Material[] $materials
 */
	class Text extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\DocumentFile
 *
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ForeignResourceId[] $foreignIds
 * @property mixed $original_filename
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Material[] $materials
 * @property-write mixed $local_path
 * @property-write mixed $remote_path
 */
	class DocumentFile extends \Eloquent {}
}

