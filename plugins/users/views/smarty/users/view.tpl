{extends file="fossil:loggedIn"}
{block name="assignations"}
{assign var="title" value=$user->name|cat:"'s profile" scope="global"}
{assign var="colStyle" value="one-col" scope="global"}
{/block}
{block name="content"}
Username: {$user->name}<br />
E-mail: {$user->email}<br />
Avatar: <img src="{$user->getAvatarURL(50)}" alt="{$user->name}'s avatar" /><br />
Gender: {$user->gender}<br />
Birthday: {$user->birthday}<br />
Timezone: {$user->timezone}<br />
Joined on: {$user->joinDate->format("Y-m-d H:i:s")}<br />
Forum posts: {$user->forumPosts|count}<br />
{/block}