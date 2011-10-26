By {link controller="user" action="view" id=$item->author->id}{$item->author->name}{/link}<br />
{$now->diff($item->postedAt)|date_interval_format}