<?php

namespace JClaveau\LogicalFilter\Entity;

/**
 * @Table(
 *      name="filter_rules",
 *      uniqueConstraints={
 *          @UniqueConstraint(
 *              name="one_rule_by_filter_and_name",
 *              columns={"filter_id", "name"}
 *          )
 *      }
 * )
 * @HasLifecycleCallbacks
 */
class FilterRules
{
    /**
     * @Id
     * @Column(type="integer", nullable=false, name="filer_rules_id")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Filter", cascade={"persist", "remove"})
     * @JoinColumn(name="filter_id", referencedColumnName="filter_id")
     */
    protected $filterId;

    /**
     * @Column(type="string", name="name", nullable=false)
     */
    protected $name;

    /**
     * @Column(type="string", name="rule", length=2000, nullable=false)
     */
    protected $rule;

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
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param mixed $filter
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
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
    public function getRule()
    {
        return is_array($this->rule) ? $this->rule : unserialize($this->rule);
    }

    /**
     * @param mixed $rule
     */
    public function setRule($rule)
    {
        $this->rule = is_array($rule) ? serialize($rule) : $rule;
    }
}