<?php
$cb = new CodeBuilder();

$class(
  $method(
    'addUser',
    $param('$user', $type('Entities\User')),
    $body(
      $if($objectCall($instanceVariable('users'), 'contains', $argument('$user')),
          $objectCall($instanceVariable('users'), 'add', $argument('$user'))
         ),
      $returnStmt($instance())
    )
  )
);


// =>
public function addUser(Entities\user $user) {
  if ($this->users->contains($user)) {
    $this->users->add($user);
  }
  return $this;
}
?>