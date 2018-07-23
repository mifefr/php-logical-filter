<?php

namespace JClaveau\LogicalFilter\Entity;

/**
 * @Entity(repositoryClass="JClaveau\LogicalFilter\Repository\FilterRepository")
 * @Table(
 *    name="filter",
 *    uniqueConstraints={
 *        @UniqueConstraint(
 *            name="one_of_each_name",
 *            columns={"name"}
 *        )
 *    }
 * )
 * @HasLifecycleCallbacks
 */
class Filter
{
    use PopulatableTrait;

    /**
     * @Id
     * @Column(type="integer", nullable=false, name="filter_id")
     * @OneToMany(targetEntity="FilterRules", mappedBy="filter_id", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @Column(name="name", type="string", length=50, nullable=false) */
    protected $name;

    /**
     * @Version
     * @Column(type="datetime", name="last_update")
     */
    protected $lastUpdate;

    /**
     * @Column(name="author", type="string", length=100, nullable=false)
     */
    protected $author;

    /**
     * This method is required to have the good value while we want to
     * json_encode an instance of this class.
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @todo list fields automatically with the EntityManager helpers?
     */
    public function toArray()
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'lastUpdate'  => $this->lastUpdate,
            'author'      => $this->author,
        ];
    }

    /**
     * clone the current object.
     *
     * @return Filter A copy of the current instance
     */
    public function copy()
    {
        return clone $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * @param mixed $lastUpdate
     */
    public function setLastUpdate($lastUpdate)
    {
        $this->lastUpdate = $lastUpdate;
    }

    /**
     * @return mixed
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param mixed $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }
}