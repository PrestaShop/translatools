<?php

class SkipDotsFilterIterator extends RecursiveFilterIterator
{
	public function accept()
	{
		return !preg_match('/^\./', $this->current()->getFilename());
	}
}